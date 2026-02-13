<?php

namespace App\Http\Controllers;

use App\Models\PlatformMember;
use App\Models\PlatformSetting;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\TenantAccount;
use App\Models\TeamMembershipInvitation;
use App\Models\Transaction;
use App\Models\IbanAccount;
use App\Models\IbanHostBank;
use App\Models\CryptoWallet;
use App\Models\CryptoWalletTransaction;
use App\Models\AccountFeeConfig;
use App\Services\WalletIdsService;
use App\Services\SolanaRpcService;
use App\Services\SolanaTransferService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Admin dashboard with stats.
     */
    public function index()
    {
        $stats = [
            'total_members' => PlatformMember::count(),
            'total_accounts' => TenantAccount::where('is_soft_deleted', false)->count(),
            'business_accounts' => TenantAccount::where('account_type', 'business_organization')
                ->where('is_soft_deleted', false)->count(),
            'pending_invitations' => TeamMembershipInvitation::where('invitation_status', 'invitation_pending')->count(),
        ];

        return view('pages.administrator.index', compact('stats'));
    }

    /**
     * Platform members search page.
     */
    public function members(Request $request)
    {
        $members = null;
        $hasSearch = $request->filled('keyword') || $request->filled('verified') || $request->filled('role');

        if ($hasSearch) {
            $query = PlatformMember::query();

            // Filter by verification status
            if ($request->filled('verified') && $request->verified !== 'all') {
                if ($request->verified === 'verified') {
                    $query->whereNotNull('email_verified_at_timestamp');
                } else {
                    $query->whereNull('email_verified_at_timestamp');
                }
            }

            // Filter by admin role
            if ($request->filled('role') && $request->role !== 'all') {
                $query->where('is_platform_administrator', $request->role === 'admin');
            }

            // Keyword search (name, email)
            if ($request->filled('keyword')) {
                $keyword = '%' . $request->keyword . '%';
                $query->where(function ($q) use ($keyword) {
                    $q->where('login_email_address', 'ilike', $keyword)
                      ->orWhere('member_first_name', 'ilike', $keyword)
                      ->orWhere('member_last_name', 'ilike', $keyword);
                });
            }

            // Apply sorting
            $sortField = $request->input('sort_field', 'email');
            $sortDir = $request->input('sort_dir', 'asc');
            $sortCreated = $request->input('sort_created', 'desc');
            
            $sortColumn = $sortField === 'name' ? 'member_first_name' : 'login_email_address';
            $query->orderBy($sortColumn, $sortDir)
                  ->orderBy('created_at_timestamp', $sortCreated);

            $members = $query->paginate(20)->withQueryString();
        }

        return view('pages.administrator.members', [
            'members' => $members,
            'hasSearch' => $hasSearch,
            'filters' => [
                'verified' => $request->input('verified', 'verified'),
                'role' => $request->input('role', 'member'),
                'keyword' => $request->input('keyword', ''),
                'sort_field' => $request->input('sort_field', 'email'),
                'sort_dir' => $request->input('sort_dir', 'asc'),
                'sort_created' => $request->input('sort_created', 'desc'),
            ],
        ]);
    }

    /**
     * Toggle admin status for a member.
     */
    public function toggleAdmin($member_id)
    {
        $member = PlatformMember::findOrFail($member_id);

        // Cannot remove own admin status
        if ($member->id === auth()->id()) {
            return back()->withErrors(['member' => 'You cannot change your own admin status.']);
        }

        $member->update([
            'is_platform_administrator' => !$member->is_platform_administrator,
        ]);

        $status = $member->is_platform_administrator ? 'granted' : 'revoked';
        return back()->with('status', "Admin access {$status} for {$member->full_name}.");
    }

    /**
     * Update a member's email address.
     */
    public function updateEmail(Request $request, $member_id)
    {
        $member = PlatformMember::findOrFail($member_id);

        $request->validate([
            'email' => 'required|email|unique:platform_members,login_email_address,' . $member_id,
        ]);

        $member->update([
            'login_email_address' => $request->input('email'),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Impersonate a member (view as them).
     */
    public function impersonate($member_id)
    {
        $member = PlatformMember::findOrFail($member_id);

        // Cannot impersonate self
        if ($member->id === auth()->id()) {
            return back()->withErrors(['member' => 'You cannot impersonate yourself.']);
        }

        // Store original admin ID to return later
        session(['admin_impersonating_from' => auth()->id()]);

        // Get member's first active account
        $firstAccount = $member->tenant_accounts()
            ->where('is_soft_deleted', false)
            ->wherePivot('membership_status', 'membership_active')
            ->first();

        if ($firstAccount) {
            session(['active_account_id' => $firstAccount->id]);
        }

        // Log in as the member using web guard
        auth('web')->login($member);

        return redirect()->route('home')
            ->with('status', "Now viewing as {$member->full_name}. Use admin panel to exit.");
    }

    /**
     * Exit impersonation.
     */
    public function exitImpersonation()
    {
        $adminId = session('admin_impersonating_from');

        if (!$adminId) {
            return redirect()->route('home');
        }

        $admin = PlatformMember::find($adminId);

        if (!$admin || !$admin->is_platform_administrator) {
            session()->forget('admin_impersonating_from');
            return redirect()->route('home');
        }

        // Log back in as admin using web guard
        auth('web')->login($admin);
        session()->forget('admin_impersonating_from');

        // Set admin's first account
        $firstAccount = $admin->tenant_accounts()
            ->where('is_soft_deleted', false)
            ->wherePivot('membership_status', 'membership_active')
            ->first();

        if ($firstAccount) {
            session(['active_account_id' => $firstAccount->id]);
        }

        return redirect()->route('admin.index')
            ->with('status', 'Exited impersonation mode.');
    }

    /**
     * Platform theme settings page.
     */
    public function theme()
    {
        $settings = [
            'platform_display_name' => PlatformSetting::getValue('platform_display_name', 'Common Portal'),
            'platform_logo_image_path' => PlatformSetting::getValue('platform_logo_image_path'),
            'platform_favicon_image_path' => PlatformSetting::getValue('platform_favicon_image_path'),
            'social_sharing_preview_image_path' => PlatformSetting::getValue('social_sharing_preview_image_path'),
            'social_sharing_meta_description' => PlatformSetting::getValue('social_sharing_meta_description'),
            'active_theme_preset_name' => PlatformSetting::getValue('active_theme_preset_name', 'default_dark'),
        ];

        return view('pages.administrator.theme', compact('settings'));
    }

    /**
     * Update platform theme settings.
     */
    public function updateTheme(Request $request)
    {
        $request->validate([
            'platform_display_name' => 'required|string|max:100',
            'social_sharing_meta_description' => 'nullable|string|max:255',
            'active_theme_preset_name' => 'required|string|in:default_dark,default_light',
        ]);

        PlatformSetting::setValue('platform_display_name', $request->platform_display_name);
        PlatformSetting::setValue('social_sharing_meta_description', $request->social_sharing_meta_description);
        PlatformSetting::setValue('active_theme_preset_name', $request->active_theme_preset_name);

        return back()->with('status', 'Theme settings updated.');
    }

    /**
     * Menu items toggle page.
     */
    public function menuItems()
    {
        $toggles = PlatformSetting::getValue('sidebar_menu_item_visibility_toggles', []);

        $menuItems = [
            'can_access_account_dashboard' => 'Dashboard',
            'can_access_customers' => 'Customers',
            'can_manage_team_members' => 'Team Management',
            'can_access_account_settings' => 'Account Settings',
            'can_access_developer_tools' => 'Developer Tools',
            'can_access_support_tickets' => 'Support Tickets',
            'can_view_transaction_history' => 'Transaction History',
            'can_view_billing_history' => 'Billing History',
            'can_view_ibans' => 'IBANs',
            'can_view_wallets' => 'Wallets',
            'can_view_fees' => 'Fees',
        ];

        return view('pages.administrator.menu-items', compact('toggles', 'menuItems'));
    }

    /**
     * Update menu item toggles.
     */
    public function updateMenuItems(Request $request)
    {
        $submittedToggles = $request->input('toggles', []);

        // Get all available menu items (must match permission keys in ViewComposerServiceProvider)
        $allMenuItems = [
            'can_access_account_dashboard',
            'can_access_customers',
            'can_manage_team_members',
            'can_access_account_settings',
            'can_access_developer_tools',
            'can_access_support_tickets',
            'can_view_transaction_history',
            'can_view_billing_history',
            'can_view_ibans',
            'can_view_wallets',
            'can_view_fees',
        ];

        // Build toggles array: explicitly set to true (checked) or false (unchecked)
        $toggles = [];
        foreach ($allMenuItems as $key) {
            $toggles[$key] = isset($submittedToggles[$key]) && $submittedToggles[$key] == '1';
        }

        PlatformSetting::setValue('sidebar_menu_item_visibility_toggles', $toggles);

        return back()->with('status', 'Menu item visibility updated.');
    }

    /**
     * Accounts search page.
     */
    public function accounts(Request $request)
    {
        $accounts = null;
        $hasSearch = $request->filled('keyword') || $request->filled('verified') || $request->filled('type');

        if ($hasSearch) {
            $query = TenantAccount::where('is_soft_deleted', false)
                ->with(['account_memberships.platform_member']);

            // Filter by account type
            if ($request->filled('type') && $request->type !== 'all') {
                $query->where('account_type', $request->type);
            }

            // Filter by owner verification status
            if ($request->filled('verified') && $request->verified !== 'all') {
                $query->whereHas('account_memberships', function ($q) use ($request) {
                    $q->where('account_membership_role', 'account_owner')
                      ->whereHas('platform_member', function ($mq) use ($request) {
                          if ($request->verified === 'verified') {
                              $mq->whereNotNull('email_verified_at_timestamp');
                          } else {
                              $mq->whereNull('email_verified_at_timestamp');
                          }
                      });
                });
            }

            // Keyword search (account name, owner name, owner email, primary contact email)
            if ($request->filled('keyword')) {
                $keyword = '%' . $request->keyword . '%';
                $query->where(function ($q) use ($keyword) {
                    $q->where('account_display_name', 'ilike', $keyword)
                      ->orWhere('primary_contact_email_address', 'ilike', $keyword)
                      ->orWhereHas('account_memberships', function ($mq) use ($keyword) {
                          $mq->where('account_membership_role', 'account_owner')
                             ->whereHas('platform_member', function ($pq) use ($keyword) {
                                 $pq->where('login_email_address', 'ilike', $keyword)
                                    ->orWhere('member_first_name', 'ilike', $keyword)
                                    ->orWhere('member_last_name', 'ilike', $keyword);
                             });
                      });
                });
            }

            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDir = $request->input('sort_dir', 'asc');
            $sortCreated = $request->input('sort_created', 'desc');
            
            $sortColumn = $sortField === 'email' ? 'primary_contact_email_address' : 'account_display_name';
            $query->orderBy($sortColumn, $sortDir)
                  ->orderBy('created_at_timestamp', $sortCreated);

            $accounts = $query->paginate(20)->withQueryString();
        }

        return view('pages.administrator.accounts', [
            'accounts' => $accounts,
            'hasSearch' => $hasSearch,
            'filters' => [
                'verified' => $request->input('verified', 'verified'),
                'type' => $request->input('type', 'all'),
                'keyword' => $request->input('keyword', ''),
                'sort_field' => $request->input('sort_field', 'name'),
                'sort_dir' => $request->input('sort_dir', 'asc'),
                'sort_created' => $request->input('sort_created', 'desc'),
            ],
        ]);
    }

    /**
     * AJAX search for members - returns JSON.
     */
    public function membersSearch(Request $request)
    {
        $query = PlatformMember::query();

        // Filter by verification status
        if ($request->filled('verified') && $request->verified !== 'all') {
            if ($request->verified === 'verified') {
                $query->whereNotNull('email_verified_at_timestamp');
            } else {
                $query->whereNull('email_verified_at_timestamp');
            }
        }

        // Filter by admin role
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('is_platform_administrator', $request->role === 'admin');
        }

        // Keyword search (name, email)
        if ($request->filled('keyword')) {
            $keyword = '%' . $request->keyword . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('login_email_address', 'ilike', $keyword)
                  ->orWhere('member_first_name', 'ilike', $keyword)
                  ->orWhere('member_last_name', 'ilike', $keyword);
            });
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'email');
        $sortDir = $request->input('sort_dir', 'asc');
        $sortCreated = $request->input('sort_created', 'desc');
        
        $sortColumn = $sortField === 'name' ? 'member_first_name' : 'login_email_address';
        $query->orderBy($sortColumn, $sortDir)
              ->orderBy('created_at_timestamp', $sortCreated);

        $members = $query->paginate(20)->withQueryString();

        $results = $members->map(function ($member) {
            return [
                'id' => $member->id,
                'hash' => $member->record_unique_identifier,
                'first_name' => $member->member_first_name,
                'last_name' => $member->member_last_name,
                'full_name' => $member->full_name ?: $member->login_email_address,
                'email' => $member->login_email_address,
                'initial' => strtoupper(substr($member->member_first_name ?: $member->login_email_address, 0, 1)),
                'joined' => $member->created_at_timestamp->format('M j, Y'),
                'joined_full' => $member->created_at_timestamp->format('M j, Y H:i'),
                'is_verified' => $member->email_verified_at_timestamp !== null,
                'verified_at' => $member->email_verified_at_timestamp?->format('M j, Y H:i'),
                'is_admin' => $member->is_platform_administrator,
                'is_self' => $member->id === auth()->id(),
                'language' => $member->preferred_language_code,
            ];
        });

        return response()->json([
            'total' => $members->total(),
            'members' => $results,
            'has_more' => $members->hasMorePages(),
            'current_page' => $members->currentPage(),
            'last_page' => $members->lastPage(),
        ]);
    }

    /**
     * AJAX search for accounts - returns JSON.
     */
    public function accountsSearch(Request $request)
    {
        $query = TenantAccount::where('is_soft_deleted', false)
            ->with(['account_memberships.platform_member']);

        // Filter by account type
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('account_type', $request->type);
        }

        // Filter by owner verification status
        if ($request->filled('verified') && $request->verified !== 'all') {
            $query->whereHas('account_memberships', function ($q) use ($request) {
                $q->where('account_membership_role', 'account_owner')
                  ->whereHas('platform_member', function ($mq) use ($request) {
                      if ($request->verified === 'verified') {
                          $mq->whereNotNull('email_verified_at_timestamp');
                      } else {
                          $mq->whereNull('email_verified_at_timestamp');
                      }
                  });
            });
        }

        // Keyword search
        if ($request->filled('keyword')) {
            $keyword = '%' . $request->keyword . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('account_display_name', 'ilike', $keyword)
                  ->orWhere('primary_contact_email_address', 'ilike', $keyword)
                  ->orWhereHas('account_memberships', function ($mq) use ($keyword) {
                      $mq->where('account_membership_role', 'account_owner')
                         ->whereHas('platform_member', function ($pq) use ($keyword) {
                             $pq->where('login_email_address', 'ilike', $keyword)
                                ->orWhere('member_first_name', 'ilike', $keyword)
                                ->orWhere('member_last_name', 'ilike', $keyword);
                         });
                  });
            });
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $sortCreated = $request->input('sort_created', 'desc');
        
        $sortColumn = $sortField === 'email' ? 'primary_contact_email_address' : 'account_display_name';
        $query->orderBy($sortColumn, $sortDir)
              ->orderBy('created_at_timestamp', $sortCreated);

        $accounts = $query->paginate(20)->withQueryString();

        $results = $accounts->map(function ($account) {
            $owner = $account->account_memberships->firstWhere('account_membership_role', 'account_owner')?->platform_member;
            return [
                'id' => $account->id,
                'name' => $account->account_display_name,
                'email' => $account->primary_contact_email_address,
                'type' => $account->account_type,
                'type_label' => $account->account_type === 'personal_individual' ? 'Personal' : 'Business',
                'owner_name' => $owner ? ($owner->full_name ?: $owner->login_email_address) : null,
                'owner_email' => $owner?->login_email_address,
                'is_verified' => $owner && $owner->email_verified_at_timestamp !== null,
                'created' => $account->created_at_timestamp->format('M j, Y'),
            ];
        });

        return response()->json([
            'total' => $accounts->total(),
            'accounts' => $results,
            'has_more' => $accounts->hasMorePages(),
            'current_page' => $accounts->currentPage(),
            'last_page' => $accounts->lastPage(),
        ]);
    }

    /**
     * Impersonate an account (view as account owner).
     */
    public function impersonateAccount(Request $request, $account_id)
    {
        $account = TenantAccount::where('is_soft_deleted', false)
            ->findOrFail($account_id);

        // Store original admin ID
        session(['admin_impersonating_from' => auth()->id()]);
        session(['active_account_id' => $account->id]);

        // If AJAX request, return JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Now impersonating: {$account->account_display_name}",
                'account_id' => $account->id,
                'account_name' => $account->account_display_name,
                'account_email' => $account->primary_contact_email_address,
            ]);
        }

        return redirect()->route('home')
            ->with('status', "Now viewing account: {$account->account_display_name}. Use admin panel to exit.");
    }

    /**
     * Support tickets list for admin.
     */
    public function supportTickets(Request $request)
    {
        return view('pages.administrator.support-tickets', [
            'categories' => SupportTicket::subjectCategories(),
        ]);
    }

    /**
     * AJAX search for support tickets.
     */
    public function supportTicketsSearch(Request $request)
    {
        $query = SupportTicket::with(['tenant_account', 'created_by_member', 'assigned_to_administrator', 'attachments']);

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('ticket_status', $request->status);
        }

        // Filter by category
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('ticket_category', $request->category);
        }

        // Keyword search
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('ticket_subject_line', 'like', "%{$keyword}%")
                  ->orWhere('ticket_description_body', 'like', "%{$keyword}%")
                  ->orWhereHas('tenant_account', function ($q2) use ($keyword) {
                      $q2->where('account_display_name', 'like', "%{$keyword}%");
                  })
                  ->orWhereHas('created_by_member', function ($q2) use ($keyword) {
                      $q2->where('profile_first_name', 'like', "%{$keyword}%")
                         ->orWhere('profile_last_name', 'like', "%{$keyword}%")
                         ->orWhere('login_email_address', 'like', "%{$keyword}%");
                  });
            });
        }

        // Sort order
        $sortCreated = $request->input('sort_created', 'desc');
        $query->orderBy('created_at_timestamp', $sortCreated);

        $tickets = $query->paginate(20);

        $results = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'subject' => $ticket->ticket_subject_line,
                'category' => $ticket->ticket_category,
                'status' => $ticket->ticket_status,
                'account_name' => $ticket->tenant_account?->account_display_name ?? 'Public',
                'created_by' => $ticket->created_by_member?->full_name ?? 'Guest',
                'created_at' => $ticket->created_at_timestamp->format('M d, Y H:i'),
                'attachments_count' => $ticket->attachments->count(),
            ];
        });

        return response()->json([
            'total' => $tickets->total(),
            'tickets' => $results,
            'has_more' => $tickets->hasMorePages(),
            'current_page' => $tickets->currentPage(),
            'last_page' => $tickets->lastPage(),
        ]);
    }

    /**
     * Get single support ticket details (for AJAX expand).
     */
    public function supportTicketShow($ticket_id)
    {
        $ticket = SupportTicket::with(['tenant_account', 'created_by_member', 'assigned_to_administrator', 'attachments.uploaded_by', 'messages.author_member', 'messages.author_admin'])
            ->findOrFail($ticket_id);

        // Get platform administrators for assignment dropdown
        $admins = PlatformMember::where('is_platform_administrator', true)
            ->orderBy('member_first_name')
            ->get(['id', 'member_first_name', 'member_last_name', 'login_email_address']);

        return response()->json([
            'ticket' => $ticket,
            'attachments' => $ticket->attachments->map(function ($att) {
                return [
                    'id' => $att->id,
                    'original_filename' => $att->original_filename,
                    'file_mime_type' => $att->file_mime_type,
                    'file_size_bytes' => $att->file_size_bytes,
                    'url' => $att->url,
                    'uploaded_by_name' => $att->uploaded_by?->full_name,
                ];
            }),
            'messages' => $ticket->messages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'message_type' => $msg->message_type,
                    'message_body' => $msg->message_body,
                    'author_name' => $msg->author_name,
                    'created_at' => $msg->created_at_timestamp->format('M j, Y g:i A'),
                ];
            }),
            'admins' => $admins->map(fn($a) => ['id' => $a->id, 'name' => trim(($a->member_first_name ?? '') . ' ' . ($a->member_last_name ?? '')) ?: $a->login_email_address]),
            'account_name' => $ticket->tenant_account?->account_display_name ?? 'N/A',
            'created_by_name' => $ticket->created_by_member?->full_name ?? 'Guest',
            'created_by_email' => $ticket->created_by_member?->login_email_address ?? 'N/A',
            'assigned_to_id' => $ticket->assigned_to_administrator_id,
            'assigned_to_name' => $ticket->assigned_to_administrator?->full_name ?? 'Unassigned',
        ]);
    }

    /**
     * Respond to a support ticket.
     */
    public function supportTicketRespond(Request $request, $ticket_id)
    {
        $request->validate([
            'response' => 'required|string|max:10000',
        ]);

        $ticket = SupportTicket::findOrFail($ticket_id);

        // Create message record
        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'author_admin_id' => auth()->id(),
            'message_type' => 'admin_response',
            'message_body' => $request->response,
        ]);
        
        $ticket->update([
            'ticket_status' => 'ticket_in_progress',
            'assigned_to_administrator_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Response added successfully.']);
    }

    /**
     * Update support ticket status.
     */
    public function supportTicketStatus(Request $request, $ticket_id)
    {
        $request->validate([
            'status' => 'required|in:ticket_open,ticket_in_progress,ticket_resolved,ticket_closed',
        ]);

        $ticket = SupportTicket::findOrFail($ticket_id);
        
        $ticket->update([
            'ticket_status' => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    /**
     * Update support ticket assignment.
     */
    public function supportTicketAssign(Request $request, $ticket_id)
    {
        $request->validate([
            'admin_id' => 'nullable|exists:platform_members,id',
        ]);

        $ticket = SupportTicket::findOrFail($ticket_id);
        
        $ticket->update([
            'assigned_to_administrator_id' => $request->admin_id ?: null,
        ]);

        return response()->json(['success' => true, 'message' => 'Assignment updated successfully.']);
    }

    /**
     * Show accounting form to record transactions.
     */
    public function accounting()
    {
        $accounts = TenantAccount::where('is_soft_deleted', false)
            ->orderBy('account_display_name')
            ->get();

        $impersonatedAccount = null;
        if (session('admin_impersonating_from') && session('active_account_id')) {
            $impersonatedAccount = TenantAccount::find(session('active_account_id'));
        }

        return view('pages.administrator.accounting', compact('accounts', 'impersonatedAccount'));
    }

    /**
     * Phase 1: Create transaction with incoming funds data.
     */
    public function storePhase1Received(Request $request)
    {
        try {
            $validated = $request->validate([
                'tenant_account_id' => 'required|exists:tenant_accounts,id',
                'currency_code' => 'required|string|max:10',
                'amount' => 'required|numeric|min:0',
                'incoming_fixed_fee' => 'nullable|numeric|min:0',
                'incoming_percentage_fee' => 'nullable|numeric|min:0',
                'incoming_minimum_fee' => 'nullable|numeric|min:0',
                'incoming_total_fee' => 'nullable|numeric|min:0',
                'datetime_received' => 'required|date',
            ]);

            $transaction = Transaction::create(array_merge($validated, [
                'transaction_status' => 'received',
                'datetime_created' => now(),
                'datetime_updated' => now(),
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Phase 1 recorded: Funds received.',
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Phase 1 error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error saving transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Phase 2: Update transaction with exchange data.
     */
    public function updatePhase2Exchanged(Request $request, $transaction_id)
    {
        try {
            $transaction = Transaction::where('id', $transaction_id)
                ->where('tenant_account_id', $request->input('tenant_account_id'))
                ->firstOrFail();

            $validated = $request->validate([
                'tenant_account_id' => 'required|exists:tenant_accounts,id',
                'settlement_currency_code' => 'nullable|string|max:10',
                'exchange_ratio' => 'nullable|numeric|min:0',
                'settlement_amount' => 'nullable|numeric|min:0',
                'exchange_fixed_fee' => 'nullable|numeric|min:0',
                'exchange_percentage_fee' => 'nullable|numeric|min:0',
                'exchange_minimum_fee' => 'nullable|numeric|min:0',
                'exchange_total_fee' => 'nullable|numeric|min:0',
                'datetime_exchanged' => 'required|date',
            ]);

            $transaction->update(array_merge($validated, [
                'transaction_status' => 'exchanged',
                'datetime_updated' => now(),
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Phase 2 recorded: Funds exchanged.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found or access denied'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Phase 2 error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Phase 3: Update transaction with settlement data.
     */
    public function updatePhase3Settled(Request $request, $transaction_id)
    {
        try {
            $transaction = Transaction::where('id', $transaction_id)
                ->where('tenant_account_id', $request->input('tenant_account_id'))
                ->firstOrFail();

            $validated = $request->validate([
                'tenant_account_id' => 'required|exists:tenant_accounts,id',
                'outgoing_fixed_fee' => 'nullable|numeric|min:0',
                'outgoing_percentage_fee' => 'nullable|numeric|min:0',
                'outgoing_minimum_fee' => 'nullable|numeric|min:0',
                'outgoing_total_fee' => 'nullable|numeric|min:0',
                'final_settlement_currency_code' => 'nullable|string|max:10',
                'final_settlement_amount' => 'nullable|numeric|min:0',
                'settlement_account_type' => 'required|in:crypto,fiat',
                'crypto_wallet_address' => 'required_if:settlement_account_type,crypto|nullable|string|max:255',
                'crypto_network' => 'required_if:settlement_account_type,crypto|nullable|string|max:50',
                'fiat_payment_method' => 'required_if:settlement_account_type,fiat|nullable|string|max:50',
                'fiat_bank_account_number' => 'required_if:settlement_account_type,fiat|nullable|string|max:100',
                'fiat_bank_routing_number' => 'nullable|string|max:100',
                'fiat_bank_swift_code' => 'nullable|string|max:50',
                'fiat_account_holder_name' => 'required_if:settlement_account_type,fiat|nullable|string|max:255',
                'fiat_bank_address' => 'nullable|string',
                'fiat_bank_country' => 'required_if:settlement_account_type,fiat|nullable|string|max:100',
                'datetime_settled' => 'required|date',
            ]);

            $transaction->update(array_merge($validated, [
                'transaction_status' => 'settled',
                'datetime_updated' => now(),
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Phase 3 recorded: Transaction settled.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found or access denied'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Phase 3 error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTransaction($transaction_hash)
    {
        try {
            // Admin can access any transaction by hash, no tenant_account_id restriction
            $transaction = Transaction::where('record_unique_identifier', $transaction_hash)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'transaction' => $transaction
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Get transaction error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * IBAN Host Banks management page.
     */
    public function ibanHostBanks()
    {
        return view('pages.administrator.iban-host-banks');
    }

    /**
     * Get list of all host banks.
     */
    public function ibanHostBanksList()
    {
        $hostBanks = IbanHostBank::notDeleted()
            ->orderBy('host_bank_name')
            ->get()
            ->map(function ($bank) {
                return [
                    'hash' => $bank->record_unique_identifier,
                    'name' => $bank->host_bank_name,
                    'is_active' => $bank->is_active,
                    'created_at' => $bank->datetime_created?->format('M j, Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'host_banks' => $hostBanks
        ]);
    }

    /**
     * Get a single host bank by hash.
     */
    public function ibanHostBankGet($hash)
    {
        try {
            $bank = IbanHostBank::where('record_unique_identifier', $hash)
                ->notDeleted()
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'host_bank' => [
                    'hash' => $bank->record_unique_identifier,
                    'name' => $bank->host_bank_name,
                    'is_active' => $bank->is_active,
                    'created_at' => $bank->datetime_created?->format('M j, Y H:i'),
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Host bank not found'
            ], 404);
        }
    }

    /**
     * Store a new host bank.
     */
    public function ibanHostBankStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'host_bank_name' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            $bank = IbanHostBank::create([
                'host_bank_name' => $validated['host_bank_name'],
                'is_active' => $validated['is_active'] ?? true,
                'is_deleted' => false,
                'datetime_created' => now(),
                'datetime_updated' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Host bank created successfully',
                'host_bank' => [
                    'hash' => $bank->record_unique_identifier,
                    'name' => $bank->host_bank_name,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Host bank store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating host bank: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing host bank.
     */
    public function ibanHostBankUpdate(Request $request, $hash)
    {
        try {
            $bank = IbanHostBank::where('record_unique_identifier', $hash)
                ->notDeleted()
                ->firstOrFail();

            $validated = $request->validate([
                'host_bank_name' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            $bank->update([
                'host_bank_name' => $validated['host_bank_name'],
                'is_active' => $validated['is_active'] ?? $bank->is_active,
                'datetime_updated' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Host bank updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Host bank not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Host bank update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating host bank: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete a host bank.
     */
    public function ibanHostBankDelete($hash)
    {
        try {
            $bank = IbanHostBank::where('record_unique_identifier', $hash)
                ->notDeleted()
                ->firstOrFail();

            $bank->softDelete();

            return response()->json([
                'success' => true,
                'message' => 'Host bank deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Host bank not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Host bank delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting host bank: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * IBAN management page.
     */
    public function ibans()
    {
        $impersonatedAccount = null;
        if (session('admin_impersonating_from') && session('active_account_id')) {
            $impersonatedAccount = TenantAccount::find(session('active_account_id'));
        }

        $accounts = TenantAccount::where('is_soft_deleted', false)
            ->orderBy('account_display_name')
            ->get();

        return view('pages.administrator.ibans', compact('impersonatedAccount', 'accounts'));
    }

    /**
     * Get list of IBANs for an account.
     */
    public function ibansList(Request $request)
    {
        $accountHash = $request->input('account_hash');
        
        if (!$accountHash) {
            return response()->json([
                'success' => false,
                'message' => 'Account hash is required',
                'ibans' => []
            ], 400);
        }

        $ibans = IbanAccount::where('account_hash', $accountHash)
            ->notDeleted()
            ->with('host_bank')
            ->orderBy('iban_friendly_name')
            ->get()
            ->map(function ($iban) {
                return [
                    'hash' => $iban->record_unique_identifier,
                    'friendly_name' => $iban->iban_friendly_name,
                    'iban_ledger' => $iban->iban_ledger,
                    'currency' => $iban->iban_currency_iso3,
                    'iban_number' => $iban->iban_number,
                    'bic_routing' => $iban->bic_routing,
                    'iban_owner' => $iban->iban_owner,
                    'host_bank_hash' => $iban->iban_host_bank_hash,
                    'host_bank_name' => $iban->host_bank?->host_bank_name,
                    'is_active' => $iban->is_active,
                    'created_at' => $iban->datetime_created?->format('M j, Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'ibans' => $ibans
        ]);
    }

    /**
     * Get a single IBAN by hash.
     */
    public function ibanGet($iban_hash)
    {
        try {
            $iban = IbanAccount::where('record_unique_identifier', $iban_hash)
                ->notDeleted()
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'iban' => [
                    'hash' => $iban->record_unique_identifier,
                    'account_hash' => $iban->account_hash,
                    'friendly_name' => $iban->iban_friendly_name,
                    'iban_ledger' => $iban->iban_ledger,
                    'currency' => $iban->iban_currency_iso3,
                    'iban_number' => $iban->iban_number,
                    'bic_routing' => $iban->bic_routing,
                    'iban_owner' => $iban->iban_owner,
                    'host_bank_hash' => $iban->iban_host_bank_hash,
                    'is_active' => $iban->is_active,
                    'created_at' => $iban->datetime_created?->format('M j, Y H:i'),
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'IBAN not found'
            ], 404);
        }
    }

    /**
     * Store a new IBAN.
     */
    public function ibanStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'account_hash' => 'required|string|max:32',
                'iban_friendly_name' => 'required|string|max:255',
                'iban_ledger' => 'nullable|string|max:36',
                'iban_currency_iso3' => 'required|string|max:3|in:AUD,CNY,EUR,GBP,MXN,USD',
                'iban_number' => 'required|string|max:34',
                'bic_routing' => 'nullable|string|max:11',
                'iban_owner' => 'nullable|string|max:255',
                'iban_host_bank_hash' => 'nullable|string|max:32',
                'is_active' => 'boolean',
            ]);

            // Verify the account exists
            $account = TenantAccount::where('record_unique_identifier', $validated['account_hash'])
                ->where('is_soft_deleted', false)
                ->first();

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found'
                ], 404);
            }

            $iban = IbanAccount::create([
                'account_hash' => $validated['account_hash'],
                'iban_friendly_name' => $validated['iban_friendly_name'],
                'iban_ledger' => $validated['iban_ledger'] ?? null,
                'iban_currency_iso3' => $validated['iban_currency_iso3'],
                'iban_number' => $validated['iban_number'],
                'bic_routing' => $validated['bic_routing'] ?? null,
                'iban_owner' => $validated['iban_owner'] ?? null,
                'iban_host_bank_hash' => $validated['iban_host_bank_hash'] ?? null,
                'creator_member_hash' => auth()->user()->record_unique_identifier,
                'is_active' => $validated['is_active'] ?? true,
                'is_deleted' => false,
                'datetime_created' => now(),
                'datetime_updated' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'IBAN created successfully',
                'iban' => [
                    'hash' => $iban->record_unique_identifier,
                    'friendly_name' => $iban->iban_friendly_name,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('IBAN store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating IBAN: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing IBAN.
     */
    public function ibanUpdate(Request $request, $iban_hash)
    {
        try {
            $iban = IbanAccount::where('record_unique_identifier', $iban_hash)
                ->notDeleted()
                ->firstOrFail();

            $validated = $request->validate([
                'iban_friendly_name' => 'required|string|max:255',
                'iban_ledger' => 'nullable|string|max:36',
                'iban_currency_iso3' => 'required|string|max:3|in:AUD,CNY,EUR,GBP,MXN,USD',
                'iban_number' => 'required|string|max:34',
                'bic_routing' => 'nullable|string|max:11',
                'iban_owner' => 'nullable|string|max:255',
                'iban_host_bank_hash' => 'nullable|string|max:32',
                'is_active' => 'boolean',
            ]);

            $iban->update([
                'iban_friendly_name' => $validated['iban_friendly_name'],
                'iban_ledger' => $validated['iban_ledger'] ?? null,
                'iban_currency_iso3' => $validated['iban_currency_iso3'],
                'iban_number' => $validated['iban_number'],
                'bic_routing' => $validated['bic_routing'] ?? null,
                'iban_owner' => $validated['iban_owner'] ?? null,
                'iban_host_bank_hash' => $validated['iban_host_bank_hash'] ?? null,
                'is_active' => $validated['is_active'] ?? $iban->is_active,
                'datetime_updated' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'IBAN updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'IBAN not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('IBAN update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating IBAN: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete an IBAN.
     */
    public function ibanDelete($iban_hash)
    {
        try {
            $iban = IbanAccount::where('record_unique_identifier', $iban_hash)
                ->notDeleted()
                ->firstOrFail();

            $iban->softDelete();

            return response()->json([
                'success' => true,
                'message' => 'IBAN deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'IBAN not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('IBAN delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting IBAN: ' . $e->getMessage()
            ], 500);
        }
    }

    // ─── Crypto Wallets Management ───────────────────────────────────────

    /**
     * Crypto wallets management page.
     */
    public function wallets()
    {
        $accounts = TenantAccount::where('is_soft_deleted', false)
            ->orderBy('account_display_name')
            ->get();

        return view('pages.administrator.wallets', compact('accounts'));
    }

    /**
     * List wallets (JSON), optionally filtered by account.
     */
    public function walletsList(Request $request)
    {
        try {
            $query = CryptoWallet::notDeleted();

            if ($request->filled('account_hash')) {
                $query->where('account_hash', $request->account_hash);
            }

            $wallets = $query->orderBy('datetime_created', 'desc')->get();

            // Fetch balances for each wallet (with delay to avoid RPC 429 rate limits)
            $solanaRpc = app(SolanaRpcService::class);
            $isFirst = true;
            $walletsWithBalances = $wallets->map(function ($wallet) use ($solanaRpc, &$isFirst) {
                $walletArray = $wallet->toArray();
                try {
                    if (!$isFirst) {
                        usleep(250000); // 250ms delay between wallets to avoid RPC rate limits
                    }
                    $isFirst = false;
                    $balances = $this->fetchWalletBalances($solanaRpc, $wallet);
                    $walletArray['token_balance'] = $balances['token_ui_amount'];
                    $walletArray['sol_balance'] = $balances['sol_balance'];
                } catch (\Exception $e) {
                    $walletArray['token_balance'] = null;
                    $walletArray['sol_balance'] = null;
                }
                return $walletArray;
            });

            return response()->json([
                'success' => true,
                'wallets' => $walletsWithBalances
            ]);
        } catch (\Exception $e) {
            \Log::error('Wallet list error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching wallets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single wallet by hash (includes SOL + token balances).
     */
    public function walletGet($hash)
    {
        try {
            $wallet = CryptoWallet::where('record_unique_identifier', $hash)
                ->notDeleted()
                ->firstOrFail();

            // Fetch balances from Solana RPC
            $solanaRpc = app(SolanaRpcService::class);
            $balances = $this->fetchWalletBalances($solanaRpc, $wallet);

            return response()->json([
                'success' => true,
                'wallet' => $wallet,
                'balances' => $balances,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found'
            ], 404);
        }
    }

    /**
     * Fetch SOL and token balances for a wallet from Solana RPC.
     */
    protected function fetchWalletBalances(SolanaRpcService $solanaRpc, CryptoWallet $wallet): array
    {
        $balances = [
            'sol_lamports' => null,
            'sol_balance' => null,
            'sol_low' => false,
            'token_balance' => null,
            'token_ui_amount' => null,
        ];

        try {
            // SOL balance (for gas fees)
            $solLamports = $solanaRpc->getBalance($wallet->wallet_address);
            if ($solLamports !== null) {
                $balances['sol_lamports'] = $solLamports;
                $balances['sol_balance'] = round($solLamports / 1_000_000_000, 6);
                $balances['sol_low'] = $solLamports < 10_000_000; // < 0.01 SOL
            }

            // SPL token balance (USDT/USDC only — skip for SOL wallets)
            $mintAddress = \App\Services\SolanaTransferService::MINTS[$wallet->wallet_currency] ?? null;
            if ($mintAddress) {
                usleep(150000); // 150ms delay between RPC calls
                $tokenAccounts = $solanaRpc->getTokenAccountsByOwner($wallet->wallet_address, $mintAddress);
                if ($tokenAccounts && isset($tokenAccounts['value']) && count($tokenAccounts['value']) > 0) {
                    $tokenInfo = $tokenAccounts['value'][0]['account']['data']['parsed']['info']['tokenAmount'] ?? null;
                    if ($tokenInfo) {
                        $balances['token_balance'] = $tokenInfo['amount'] ?? '0';
                        $balances['token_ui_amount'] = $tokenInfo['uiAmount'] ?? 0;
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch wallet balances', ['wallet' => $wallet->wallet_address, 'error' => $e->getMessage()]);
        }

        return $balances;
    }

    /**
     * Create a new crypto wallet via WalletIDs.net API.
     */
    public function walletStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'wallet_friendly_name' => 'required|string|max:255',
                'wallet_currency' => 'required|in:USDT,USDC,EURC,SOL',
                'wallet_network' => 'required|in:solana',
                'wallet_type' => 'required|in:client,admin,gas',
                'account_hash' => 'required|string|max:32',
            ]);

            // Only one GAS wallet allowed per network
            if ($validated['wallet_type'] === 'gas') {
                $existingGas = CryptoWallet::where('wallet_type', 'gas')
                    ->where('wallet_network', $validated['wallet_network'])
                    ->notDeleted()
                    ->first();
                if ($existingGas) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A GAS wallet already exists for the ' . $validated['wallet_network'] . ' network. Only one GAS wallet per network is allowed.'
                    ], 422);
                }
            }

            // Call WalletIDs.net to create the wallet
            $walletIdsService = app(WalletIdsService::class);

            $webhookUrl = route('webhooks.walletids');
            $externalId = $validated['account_hash'] . '_' . time();

            $apiResponse = $walletIdsService->createWallet(
                $validated['wallet_network'],
                strtolower($validated['wallet_currency']),
                'standalone',
                $validated['wallet_friendly_name'],
                $externalId,
                $webhookUrl
            );

            $walletData = $apiResponse['data']['wallet'] ?? null;

            if (!$apiResponse || !$walletData || !isset($walletData['hash'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create wallet via WalletIDs.net. Please check API credentials and try again.'
                ], 502);
            }

            $wallet = CryptoWallet::create([
                'account_hash' => $validated['account_hash'],
                'wallet_friendly_name' => $validated['wallet_friendly_name'],
                'wallet_currency' => $validated['wallet_currency'],
                'wallet_network' => $validated['wallet_network'],
                'wallet_type' => $validated['wallet_type'],
                'wallet_address' => $walletData['wallet_address'] ?? '',
                'walletids_wallet_hash' => $walletData['hash'],
                'walletids_external_id' => $externalId,
                'creator_member_hash' => auth()->user()->record_unique_identifier,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Wallet created successfully',
                'wallet' => $wallet
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Wallet create error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating wallet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a crypto wallet.
     */
    public function walletUpdate(Request $request, $hash)
    {
        try {
            $wallet = CryptoWallet::where('record_unique_identifier', $hash)
                ->notDeleted()
                ->firstOrFail();

            $validated = $request->validate([
                'wallet_friendly_name' => 'required|string|max:255',
                'account_hash' => 'required|string|max:32',
                'is_active' => 'required|boolean',
            ]);

            $wallet->update(array_merge($validated, [
                'datetime_updated' => now(),
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Wallet updated successfully',
                'wallet' => $wallet->fresh()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Wallet update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating wallet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete a crypto wallet.
     */
    public function walletDelete($hash)
    {
        try {
            $wallet = CryptoWallet::where('record_unique_identifier', $hash)
                ->notDeleted()
                ->firstOrFail();

            $wallet->softDelete();

            return response()->json([
                'success' => true,
                'message' => 'Wallet deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Wallet delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting wallet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin-initiated send (sub-distribution from master wallet to client wallet).
     */
    public function walletSend(Request $request, $hash)
    {
        try {
            $wallet = CryptoWallet::where('record_unique_identifier', $hash)
                ->notDeleted()
                ->active()
                ->firstOrFail();

            $validated = $request->validate([
                'to_wallet_address' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0.000001',
                'memo_note' => 'nullable|string|max:1000',
            ]);

            // Record the transaction as submitted
            $tx = CryptoWalletTransaction::create([
                'wallet_id' => $wallet->id,
                'account_hash' => $wallet->account_hash,
                'direction' => 'outgoing',
                'currency' => $wallet->wallet_currency,
                'network' => $wallet->wallet_network,
                'amount' => $validated['amount'],
                'from_wallet_address' => $wallet->wallet_address,
                'to_wallet_address' => $validated['to_wallet_address'],
                'transaction_status' => 'submitted',
                'memo_note' => $validated['memo_note'] ?? null,
                'initiated_by_member_hash' => auth()->user()->record_unique_identifier,
                'datetime_submitted' => now(),
            ]);

            // Execute the Solana SPL token transfer
            $transferService = app(SolanaTransferService::class);
            $result = $transferService->transfer(
                $wallet,
                $validated['to_wallet_address'],
                (float) $validated['amount'],
                $tx
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transfer completed successfully.',
                    'signature' => $result['signature'],
                    'transaction' => $tx->fresh(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Transfer failed.',
                    'transaction' => $tx->fresh(),
                ], 502);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found or inactive'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Wallet send error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending from wallet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * WalletIDs.net webhook receiver.
     * Handles payment_detected and balance_changed events.
     */
    public function walletIdsWebhook(Request $request)
    {
        try {
            // Verify webhook signature
            $walletIdsService = app(WalletIdsService::class);
            $signature = $request->header('X-Webhook-Signature', '');
            $secret = config('services.walletids.webhook_secret');

            if ($secret && !$walletIdsService->verifyWebhookSignature(
                $request->getContent(),
                $signature,
                $secret
            )) {
                \Log::warning('WalletIDs webhook: invalid signature');
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $event = $request->input('event');
            $walletAddress = $request->input('wallet_address');
            $amount = $request->input('amount');
            $currency = $request->input('currency');
            $network = $request->input('network');
            $txHash = $request->input('tx_hash');
            $fromAddress = $request->input('from_address');
            $externalId = $request->input('external_id');

            \Log::info('WalletIDs webhook received', [
                'event' => $event,
                'wallet_address' => $walletAddress,
                'amount' => $amount,
                'currency' => $currency,
                'tx_hash' => $txHash,
            ]);

            if ($event === 'payment_detected' && $walletAddress) {
                // Find the wallet in our system
                $wallet = CryptoWallet::where('wallet_address', $walletAddress)
                    ->notDeleted()
                    ->first();

                if ($wallet) {
                    // Check for duplicate (same tx_hash)
                    $existing = CryptoWalletTransaction::where('solana_tx_signature', $txHash)->first();

                    if (!$existing && $txHash) {
                        CryptoWalletTransaction::create([
                            'wallet_id' => $wallet->id,
                            'account_hash' => $wallet->account_hash,
                            'direction' => 'incoming',
                            'currency' => strtoupper($currency ?? $wallet->wallet_currency),
                            'network' => $network ?? $wallet->wallet_network,
                            'amount' => $amount ?? 0,
                            'from_wallet_address' => $fromAddress ?? 'unknown',
                            'to_wallet_address' => $walletAddress,
                            'solana_tx_signature' => $txHash,
                            'transaction_status' => 'confirmed',
                            'webhook_detected' => true,
                            'datetime_submitted' => now(),
                            'datetime_confirmed' => now(),
                        ]);
                    }
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('WalletIDs webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    // ─── Account Fees Management ──────────────────────────────────────────

    /**
     * Account Fees management page.
     */
    public function fees()
    {
        $impersonatedAccount = null;
        if (session('admin_impersonating_from') && session('active_account_id')) {
            $impersonatedAccount = TenantAccount::find(session('active_account_id'));
        }

        $accounts = TenantAccount::where('is_soft_deleted', false)
            ->orderBy('account_display_name')
            ->get();

        return view('pages.administrator.fees', compact('impersonatedAccount', 'accounts'));
    }

    /**
     * Get fee configs for an account (GBP and EUR).
     */
    public function feesList(Request $request)
    {
        $accountHash = $request->input('account_hash');
        
        if (!$accountHash) {
            return response()->json([
                'success' => false,
                'message' => 'Account hash is required',
                'fees' => []
            ], 400);
        }

        $fees = AccountFeeConfig::where('account_hash', $accountHash)
            ->notDeleted()
            ->whereIn('currency_code', ['GBP', 'EUR'])
            ->get()
            ->keyBy('currency_code')
            ->map(function ($fee) {
                return [
                    'hash' => $fee->record_unique_identifier,
                    'currency_code' => $fee->currency_code,
                    'fixed_fee' => $fee->fixed_fee,
                    'percentage_fee' => $fee->percentage_fee,
                    'minimum_fee' => $fee->minimum_fee,
                    'is_active' => $fee->is_active,
                ];
            });

        return response()->json([
            'success' => true,
            'fees' => [
                'GBP' => $fees->get('GBP'),
                'EUR' => $fees->get('EUR'),
            ]
        ]);
    }

    /**
     * Store or update fee config for a currency.
     */
    public function feesStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'account_hash' => 'required|string|max:32',
                'currency_code' => 'required|string|in:GBP,EUR',
                'fixed_fee' => 'required|numeric|min:0',
                'percentage_fee' => 'required|numeric|min:0|max:100',
                'minimum_fee' => 'required|numeric|min:0',
            ]);

            $account = TenantAccount::where('record_unique_identifier', $validated['account_hash'])
                ->where('is_soft_deleted', false)
                ->first();

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found'
                ], 404);
            }

            $feeConfig = AccountFeeConfig::where('account_hash', $validated['account_hash'])
                ->where('currency_code', $validated['currency_code'])
                ->notDeleted()
                ->first();

            if ($feeConfig) {
                $feeConfig->update([
                    'fixed_fee' => $validated['fixed_fee'],
                    'percentage_fee' => $validated['percentage_fee'],
                    'minimum_fee' => $validated['minimum_fee'],
                    'datetime_updated' => now(),
                ]);
                $message = 'Fee config updated successfully';
            } else {
                $feeConfig = AccountFeeConfig::create([
                    'account_hash' => $validated['account_hash'],
                    'currency_code' => $validated['currency_code'],
                    'fixed_fee' => $validated['fixed_fee'],
                    'percentage_fee' => $validated['percentage_fee'],
                    'minimum_fee' => $validated['minimum_fee'],
                    'creator_member_hash' => auth()->user()->record_unique_identifier,
                    'is_active' => true,
                    'is_deleted' => false,
                    'datetime_created' => now(),
                    'datetime_updated' => now(),
                ]);
                $message = 'Fee config created successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'fee' => [
                    'hash' => $feeConfig->record_unique_identifier,
                    'currency_code' => $feeConfig->currency_code,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Fee config store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error saving fee config: ' . $e->getMessage()
            ], 500);
        }
    }
}
