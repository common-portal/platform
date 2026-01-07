<?php

namespace App\Http\Controllers;

use App\Models\TenantAccount;
use App\Models\TenantAccountMembership;
use App\Models\PlatformMember;
use App\Models\TeamMembershipInvitation;
use App\Services\PlatformMailerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    /**
     * Show team members list.
     */
    public function index()
    {
        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'No active account selected.']);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->where('is_soft_deleted', false)
            ->first();

        if (!$account) {
            return redirect()->route('home')->withErrors(['account' => 'Account not found.']);
        }

        // Get current user's membership to check permissions
        $currentMembership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$currentMembership || !$currentMembership->canManageTeam()) {
            abort(403, 'You do not have permission to manage this team.');
        }

        // Get all memberships for this account
        $memberships = TenantAccountMembership::where('tenant_account_id', $activeAccountId)
            ->with('platform_member')
            ->orderByRaw("CASE account_membership_role 
                WHEN 'account_owner' THEN 1 
                WHEN 'account_administrator' THEN 2 
                ELSE 3 END")
            ->orderBy('created_at_timestamp', 'asc')
            ->get();

        // Get pending invitations
        $pendingInvitations = TeamMembershipInvitation::where('tenant_account_id', $activeAccountId)
            ->where('invitation_status', 'invitation_pending')
            ->orderBy('created_at_timestamp', 'desc')
            ->get();

        return view('pages.account.team', [
            'account' => $account,
            'memberships' => $memberships,
            'pendingInvitations' => $pendingInvitations,
            'currentMembership' => $currentMembership,
            'allPermissions' => TenantAccountMembership::allPermissionSlugs(),
        ]);
    }

    /**
     * Update a member's permissions.
     */
    public function updatePermissions(Request $request, $membership_id)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string|in:' . implode(',', TenantAccountMembership::allPermissionSlugs()),
        ]);

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return back()->withErrors(['account' => 'No active account selected.']);
        }

        // Get current user's membership
        $currentMembership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$currentMembership || !$currentMembership->canManageTeam()) {
            return back()->withErrors(['permission' => 'You do not have permission to manage this team.']);
        }

        // Get target membership
        $targetMembership = TenantAccountMembership::where('id', $membership_id)
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$targetMembership) {
            return back()->withErrors(['membership' => 'Member not found.']);
        }

        // Cannot edit owner permissions
        if ($targetMembership->account_membership_role === 'account_owner') {
            return back()->withErrors(['membership' => 'Cannot modify owner permissions.']);
        }

        // Update with self-protection check
        $result = $targetMembership->updatePermissions(
            $request->permissions ?? [],
            auth()->id()
        );

        if (!$result['success']) {
            return back()->withErrors(['permission' => $result['message']]);
        }

        return back()->with('status', $result['message']);
    }

    /**
     * Revoke (disable) a member's access.
     */
    public function revoke($membership_id)
    {
        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return back()->withErrors(['account' => 'No active account selected.']);
        }

        $currentMembership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$currentMembership || !$currentMembership->canManageTeam()) {
            return back()->withErrors(['permission' => 'You do not have permission to manage this team.']);
        }

        $targetMembership = TenantAccountMembership::where('id', $membership_id)
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$targetMembership) {
            return back()->withErrors(['membership' => 'Member not found.']);
        }

        // Cannot revoke owner
        if ($targetMembership->account_membership_role === 'account_owner') {
            return back()->withErrors(['membership' => 'Cannot revoke owner access.']);
        }

        // Cannot revoke self
        if ($targetMembership->platform_member_id === auth()->id()) {
            return back()->withErrors(['membership' => 'You cannot revoke your own access. Ask another team manager.']);
        }

        $targetMembership->revoke();

        return back()->with('status', 'Member access revoked.');
    }

    /**
     * Reactivate a revoked member.
     */
    public function reactivate($membership_id)
    {
        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return back()->withErrors(['account' => 'No active account selected.']);
        }

        $currentMembership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$currentMembership || !$currentMembership->canManageTeam()) {
            return back()->withErrors(['permission' => 'You do not have permission to manage this team.']);
        }

        $targetMembership = TenantAccountMembership::where('id', $membership_id)
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$targetMembership) {
            return back()->withErrors(['membership' => 'Member not found.']);
        }

        $targetMembership->reactivate();

        return back()->with('status', 'Member access restored.');
    }

    /**
     * Show invite form (placeholder for Phase 6).
     */
    public function showInvite()
    {
        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'No active account selected.']);
        }

        $currentMembership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$currentMembership || !$currentMembership->canManageTeam()) {
            abort(403, 'You do not have permission to invite team members.');
        }

        return view('pages.account.team-invite', [
            'allPermissions' => TenantAccountMembership::allPermissionSlugs(),
            'defaultPermissions' => TenantAccountMembership::defaultTeamMemberPermissions(),
        ]);
    }

    /**
     * Send invitation (placeholder for Phase 6).
     */
    public function sendInvite(Request $request)
    {
        // Full implementation in Phase 6
        return back()->with('status', 'Invitation functionality coming in Phase 6.');
    }
}
