<?php

namespace App\Http\Controllers;

use App\Models\TeamMembershipInvitation;
use App\Models\TenantAccountMembership;
use App\Models\PlatformMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvitationController extends Controller
{
    /**
     * Show invitation acceptance page.
     */
    public function show($token)
    {
        $invitation = TeamMembershipInvitation::where('record_unique_identifier', $token)
            ->with(['tenant_account', 'invited_by_member'])
            ->first();

        if (!$invitation) {
            return view('pages.invitation-invalid', [
                'reason' => 'Invitation not found.',
            ]);
        }

        if ($invitation->isAccepted()) {
            return view('pages.invitation-invalid', [
                'reason' => 'This invitation has already been accepted.',
            ]);
        }

        if ($invitation->isExpired()) {
            return view('pages.invitation-invalid', [
                'reason' => 'This invitation has expired or been cancelled.',
            ]);
        }

        // Check if account still exists
        if (!$invitation->tenant_account || $invitation->tenant_account->is_soft_deleted) {
            return view('pages.invitation-invalid', [
                'reason' => 'The account this invitation was for no longer exists.',
            ]);
        }

        return view('pages.invitation-accept', [
            'invitation' => $invitation,
            'account' => $invitation->tenant_account,
            'inviter' => $invitation->invited_by_member,
        ]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(Request $request, $token)
    {
        $invitation = TeamMembershipInvitation::where('record_unique_identifier', $token)
            ->with('tenant_account')
            ->first();

        if (!$invitation || !$invitation->isPending()) {
            return redirect()->route('home')
                ->withErrors(['invitation' => 'Invalid or expired invitation.']);
        }

        if (!$invitation->tenant_account || $invitation->tenant_account->is_soft_deleted) {
            return redirect()->route('home')
                ->withErrors(['invitation' => 'The account no longer exists.']);
        }

        $user = auth()->user();

        // If not logged in, redirect to login with invitation token
        if (!$user) {
            session(['pending_invitation_token' => $token]);
            return redirect()->route('login-register')
                ->with('status', 'Please log in or register to accept the invitation.');
        }

        // Verify email matches invitation
        if (strtolower($user->login_email_address) !== strtolower($invitation->invited_email_address)) {
            return back()->withErrors(['email' => 'You must be logged in as ' . $invitation->invited_email_address . ' to accept this invitation.']);
        }

        // Check if already a member
        $existingMembership = TenantAccountMembership::where('tenant_account_id', $invitation->tenant_account_id)
            ->where('platform_member_id', $user->id)
            ->first();

        if ($existingMembership) {
            $invitation->accept();
            return redirect()->route('home')
                ->with('status', 'You are already a member of this account.');
        }

        DB::beginTransaction();

        try {
            // Create membership with invited permissions
            TenantAccountMembership::create([
                'tenant_account_id' => $invitation->tenant_account_id,
                'platform_member_id' => $user->id,
                'account_membership_role' => 'account_team_member',
                'granted_permission_slugs' => $invitation->invited_permission_slugs ?? TenantAccountMembership::defaultTeamMemberPermissions(),
                'membership_status' => 'membership_active',
                'membership_accepted_at_timestamp' => now(),
            ]);

            // Mark invitation as accepted
            $invitation->accept();

            DB::commit();

            // Switch to the new account
            session(['active_account_id' => $invitation->tenant_account_id]);

            return redirect()->route('home')
                ->with('status', 'Welcome! You have joined ' . $invitation->tenant_account->account_display_name . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['invitation' => 'Failed to accept invitation. Please try again.']);
        }
    }
}
