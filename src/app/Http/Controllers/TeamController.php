<?php

namespace App\Http\Controllers;

use App\Models\TenantAccountMembership;
use App\Models\TeamMembershipInvitation;
use App\Models\PlatformMember;
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
     * Show invite form.
     */
    public function showInvite()
    {
        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'No active account selected.']);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->where('is_soft_deleted', false)
            ->first();

        $currentMembership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$currentMembership || !$currentMembership->canManageTeam()) {
            abort(403, 'You do not have permission to invite team members.');
        }

        return view('pages.account.team-invite', [
            'account' => $account,
            'allPermissions' => TenantAccountMembership::allPermissionSlugs(),
            'defaultPermissions' => TenantAccountMembership::defaultTeamMemberPermissions(),
        ]);
    }

    /**
     * Send invitation email.
     */
    public function sendInvite(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'permissions' => 'array',
            'permissions.*' => 'string|in:' . implode(',', TenantAccountMembership::allPermissionSlugs()),
        ]);

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return back()->withErrors(['account' => 'No active account selected.']);
        }

        $currentMembership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$currentMembership || !$currentMembership->canManageTeam()) {
            return back()->withErrors(['permission' => 'You do not have permission to invite team members.']);
        }

        $email = strtolower(trim($request->email));

        // Check if already a member
        $existingMember = PlatformMember::where('login_email_address', $email)->first();
        if ($existingMember) {
            $existingMembership = TenantAccountMembership::where('tenant_account_id', $activeAccountId)
                ->where('platform_member_id', $existingMember->id)
                ->first();
            
            if ($existingMembership) {
                return back()->withErrors(['email' => 'This person is already a member of this account.']);
            }
        }

        // Check if pending invitation exists
        $existingInvitation = TeamMembershipInvitation::where('tenant_account_id', $activeAccountId)
            ->where('invited_email_address', $email)
            ->where('invitation_status', 'invitation_pending')
            ->first();

        if ($existingInvitation) {
            return back()->withErrors(['email' => 'A pending invitation already exists for this email. Use resend if needed.']);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->first();

        DB::beginTransaction();

        try {
            // Create invitation
            $invitation = TeamMembershipInvitation::create([
                'tenant_account_id' => $activeAccountId,
                'invited_email_address' => $email,
                'invited_by_member_id' => auth()->id(),
                'invited_permission_slugs' => $request->permissions ?? TenantAccountMembership::defaultTeamMemberPermissions(),
                'invitation_status' => 'invitation_pending',
                'invitation_last_sent_at_timestamp' => now(),
            ]);

            // Send invitation email
            $acceptUrl = route('invitation.accept', ['token' => $invitation->record_unique_identifier]);
            $mailer = new PlatformMailerService();
            $mailer->sendInvitationEmail(
                recipientEmail: $email,
                inviterName: auth()->user()->full_name,
                accountName: $account->account_display_name,
                acceptUrl: $acceptUrl
            );

            DB::commit();

            return redirect()->route('account.team')
                ->with('status', "Invitation sent to {$email}!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['email' => 'Failed to send invitation. Please try again.']);
        }
    }

    /**
     * Resend an invitation.
     */
    public function resendInvite($invitation_id)
    {
        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return back()->withErrors(['account' => 'No active account selected.']);
        }

        $currentMembership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$currentMembership || !$currentMembership->canManageTeam()) {
            return back()->withErrors(['permission' => 'You do not have permission to manage invitations.']);
        }

        $invitation = TeamMembershipInvitation::where('id', $invitation_id)
            ->where('tenant_account_id', $activeAccountId)
            ->where('invitation_status', 'invitation_pending')
            ->first();

        if (!$invitation) {
            return back()->withErrors(['invitation' => 'Invitation not found.']);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->first();

        // Send invitation email
        $acceptUrl = route('invitation.accept', ['token' => $invitation->record_unique_identifier]);
        $mailer = new PlatformMailerService();
        $mailer->sendInvitationEmail(
            recipientEmail: $invitation->invited_email_address,
            inviterName: auth()->user()->full_name,
            accountName: $account->account_display_name,
            acceptUrl: $acceptUrl
        );

        $invitation->recordResend();

        return back()->with('status', "Invitation resent to {$invitation->invited_email_address}!");
    }

    /**
     * Cancel a pending invitation.
     */
    public function cancelInvite($invitation_id)
    {
        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return back()->withErrors(['account' => 'No active account selected.']);
        }

        $currentMembership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$currentMembership || !$currentMembership->canManageTeam()) {
            return back()->withErrors(['permission' => 'You do not have permission to manage invitations.']);
        }

        $invitation = TeamMembershipInvitation::where('id', $invitation_id)
            ->where('tenant_account_id', $activeAccountId)
            ->where('invitation_status', 'invitation_pending')
            ->first();

        if (!$invitation) {
            return back()->withErrors(['invitation' => 'Invitation not found.']);
        }

        $invitation->expire();

        return back()->with('status', 'Invitation cancelled.');
    }
}
