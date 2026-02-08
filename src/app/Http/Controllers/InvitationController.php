<?php

namespace App\Http\Controllers;

use App\Models\PlatformMember;
use App\Models\TeamMembershipInvitation;
use App\Models\TenantAccount;
use App\Models\TenantAccountMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvitationController extends Controller
{
    /**
     * Show invitation acceptance page.
     * 
     * Clicking the invite link IS email verification - so we auto-create/login
     * the user and accept the invitation immediately.
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

        // If user is already logged in with the correct email, show acceptance page
        if (auth()->check()) {
            if (strtolower(auth()->user()->login_email_address) === strtolower($invitation->invited_email_address)) {
                return view('pages.invitation-accept', [
                    'invitation' => $invitation,
                    'account' => $invitation->tenant_account,
                    'inviter' => $invitation->invited_by_member,
                ]);
            }
            // Wrong account - show the page to let them switch
            return view('pages.invitation-accept', [
                'invitation' => $invitation,
                'account' => $invitation->tenant_account,
                'inviter' => $invitation->invited_by_member,
            ]);
        }

        // User is not logged in - clicking the invite link IS email verification
        // Auto-create account if needed, login, and accept invitation
        return $this->autoAcceptInvitation($invitation);
    }

    /**
     * Auto-create account (if needed), login user, and accept invitation.
     * This treats clicking the invite link as email verification.
     */
    protected function autoAcceptInvitation(TeamMembershipInvitation $invitation)
    {
        $email = strtolower($invitation->invited_email_address);

        DB::beginTransaction();

        try {
            // Find or create the member
            $member = PlatformMember::where('login_email_address', $email)->first();
            $isNewMember = false;

            if (!$member) {
                // New member - create member + personal account
                $member = PlatformMember::create([
                    'login_email_address' => $email,
                    'preferred_language_code' => session('preferred_language', 'eng'),
                ]);

                // Create personal account
                $personalAccount = TenantAccount::create([
                    'account_display_name' => 'Personal',
                    'account_type' => 'personal_individual',
                    'primary_contact_email_address' => $email,
                ]);

                // Create membership as owner of personal account
                TenantAccountMembership::create([
                    'tenant_account_id' => $personalAccount->id,
                    'platform_member_id' => $member->id,
                    'account_membership_role' => 'account_owner',
                    'granted_permission_slugs' => [
                        'can_access_account_settings',
                        'can_access_account_dashboard',
                        'can_manage_team_members',
                    ],
                    'membership_status' => 'membership_active',
                    'membership_accepted_at_timestamp' => now(),
                ]);

                $isNewMember = true;
            }

            // Check if already a member of the invited account
            $existingMembership = TenantAccountMembership::where('tenant_account_id', $invitation->tenant_account_id)
                ->where('platform_member_id', $member->id)
                ->first();

            if (!$existingMembership) {
                // Create membership with invited permissions
                TenantAccountMembership::create([
                    'tenant_account_id' => $invitation->tenant_account_id,
                    'platform_member_id' => $member->id,
                    'account_membership_role' => 'account_team_member',
                    'granted_permission_slugs' => $invitation->invited_permission_slugs ?? TenantAccountMembership::defaultTeamMemberPermissions(),
                    'membership_status' => 'membership_active',
                    'membership_accepted_at_timestamp' => now(),
                ]);
            }

            // Mark invitation as accepted
            $invitation->accept();

            DB::commit();

            // Log the user in
            Auth::login($member);

            // Switch to the invited account
            session(['active_account_id' => $invitation->tenant_account_id]);

            $welcomeMessage = $isNewMember
                ? 'Welcome! Your account has been created and you have joined ' . $invitation->tenant_account->account_display_name . '.'
                : 'Welcome! You have joined ' . $invitation->tenant_account->account_display_name . '.';

            return redirect()->route('home')->with('status', $welcomeMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            return view('pages.invitation-invalid', [
                'reason' => 'Failed to process invitation. Please try again.',
            ]);
        }
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
