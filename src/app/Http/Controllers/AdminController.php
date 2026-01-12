<?php

namespace App\Http\Controllers;

use App\Models\PlatformMember;
use App\Models\PlatformSetting;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\TenantAccount;
use App\Models\TeamMembershipInvitation;
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
            'dashboard' => 'Dashboard',
            'team' => 'Team Management',
            'settings' => 'Account Settings',
            'developer' => 'Developer Tools',
            'support' => 'Support Tickets',
            'transactions' => 'Transaction History',
            'billing' => 'Billing History',
        ];

        return view('pages.administrator.menu-items', compact('toggles', 'menuItems'));
    }

    /**
     * Update menu item toggles.
     */
    public function updateMenuItems(Request $request)
    {
        $toggles = $request->input('toggles', []);

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
    public function impersonateAccount($account_id)
    {
        $account = TenantAccount::where('is_soft_deleted', false)
            ->findOrFail($account_id);

        // Store original admin ID
        session(['admin_impersonating_from' => auth()->id()]);
        session(['active_account_id' => $account->id]);

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
}
