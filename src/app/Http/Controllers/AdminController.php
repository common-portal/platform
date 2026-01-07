<?php

namespace App\Http\Controllers;

use App\Models\PlatformMember;
use App\Models\PlatformSetting;
use App\Models\TenantAccount;
use App\Models\TeamMembershipInvitation;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Admin middleware check.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()?->is_platform_administrator) {
                abort(403, 'Administrator access required.');
            }
            return $next($request);
        });
    }

    /**
     * Admin dashboard with stats.
     */
    public function index()
    {
        $stats = [
            'total_members' => PlatformMember::count(),
            'total_accounts' => TenantAccount::where('is_soft_deleted', false)->count(),
            'business_accounts' => TenantAccount::where('account_type', 'business_entity')
                ->where('is_soft_deleted', false)->count(),
            'pending_invitations' => TeamMembershipInvitation::where('invitation_status', 'invitation_pending')->count(),
        ];

        return view('pages.administrator.index', compact('stats'));
    }

    /**
     * Platform members list.
     */
    public function members()
    {
        $members = PlatformMember::orderBy('created_at_timestamp', 'desc')
            ->paginate(20);

        return view('pages.administrator.members', compact('members'));
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

        // Log in as the member
        auth()->login($member);

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

        // Log back in as admin
        auth()->login($admin);
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
     * Accounts list.
     */
    public function accounts()
    {
        $accounts = TenantAccount::where('is_soft_deleted', false)
            ->with('owner')
            ->orderBy('created_at_timestamp', 'desc')
            ->paginate(20);

        return view('pages.administrator.accounts', compact('accounts'));
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
}
