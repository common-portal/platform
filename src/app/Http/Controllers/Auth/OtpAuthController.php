<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PlatformMember;
use App\Models\TenantAccount;
use App\Models\TenantAccountMembership;
use App\Models\OneTimePasswordToken;
use App\Services\PlatformMailerService;
use App\Services\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class OtpAuthController extends Controller
{
    /**
     * Show the login/register form.
     */
    public function showLoginRegister()
    {
        return view('pages.login-register');
    }

    /**
     * Handle email submission - send OTP.
     * Creates new member + personal account if email doesn't exist.
     */
    public function sendOtp(Request $request)
    {
        // Verify reCAPTCHA
        $recaptcha = new RecaptchaService();
        $recaptchaResult = $recaptcha->verify($request->input('recaptcha_token'), 'otp_send');
        
        if (!$recaptchaResult['success']) {
            return back()->withErrors(['email' => $recaptchaResult['error']])->withInput();
        }

        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($request->email));
        $isNewMember = false;

        DB::beginTransaction();

        try {
            $member = PlatformMember::where('login_email_address', $email)->first();

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

                // Create membership as owner
                TenantAccountMembership::create([
                    'tenant_account_id' => $personalAccount->id,
                    'platform_member_id' => $member->id,
                    'account_membership_role' => 'account_owner',
                    'granted_permission_slugs' => json_encode([
                        'can_access_account_settings',
                        'can_access_account_dashboard',
                        'can_manage_team_members',
                    ]),
                    'membership_status' => 'membership_active',
                    'membership_accepted_at_timestamp' => now(),
                ]);

                $isNewMember = true;
            }

            // Generate OTP
            $otpData = OneTimePasswordToken::createForMember($member);

            // Send OTP via email
            $this->sendOtpEmail($member, $otpData['plain_code'], $isNewMember);

            DB::commit();

            // Store email in session for verification step
            session(['otp_email' => $email, 'otp_member_id' => $member->id]);

            return redirect()->route('otp.verify.form')->with('status', 
                $isNewMember 
                    ? __translator('Welcome! We\'ve sent a verification code to your email.')
                    : __translator('We\'ve sent a verification code to your email.')
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['email' => __translator('Failed to send verification code. Please try again.')]);
        }
    }

    /**
     * Show the OTP verification form.
     */
    public function showVerifyForm()
    {
        if (!session('otp_email')) {
            return redirect()->route('login-register');
        }

        return view('pages.otp-verify', [
            'email' => session('otp_email'),
        ]);
    }

    /**
     * Verify the OTP code.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $memberId = session('otp_member_id');
        $email = session('otp_email');

        if (!$memberId || !$email) {
            return redirect()->route('login-register')
                ->withErrors(['email' => __translator('Session expired. Please start again.')]);
        }

        $member = PlatformMember::find($memberId);

        if (!$member) {
            return redirect()->route('login-register')
                ->withErrors(['email' => __translator('Member not found. Please start again.')]);
        }

        // Find valid OTP tokens for this member
        $validTokens = $member->one_time_password_tokens()
            ->valid()
            ->orderBy('created_at_timestamp', 'desc')
            ->get();

        $verified = false;
        $matchedToken = null;

        foreach ($validTokens as $token) {
            if ($token->verifyCode($request->code)) {
                $verified = true;
                $matchedToken = $token;
                break;
            }
        }

        if (!$verified) {
            return back()->withErrors(['code' => __translator('Invalid or expired code. Please try again.')]);
        }

        // Mark token as used and invalidate others
        $matchedToken->markAsUsed();
        OneTimePasswordToken::invalidateAllForMember($member->id);

        // Mark email as verified if not already
        if (!$member->email_verified_at_timestamp) {
            $member->update(['email_verified_at_timestamp' => now()]);
        }

        // Clear OTP session data
        session()->forget(['otp_email', 'otp_member_id']);

        // Log the member in
        Auth::login($member, true);

        // Set active account to first available
        $firstAccount = $member->tenant_accounts()
            ->wherePivot('membership_status', 'membership_active')
            ->first();

        if ($firstAccount) {
            session(['active_account_id' => $firstAccount->id]);
        }

        // Check for pending invitation
        if ($pendingToken = session('pending_invitation_token')) {
            session()->forget('pending_invitation_token');
            return redirect()->route('invitation.accept', ['token' => $pendingToken]);
        }

        return redirect()->route('home')->with('status', __translator('Welcome back!'));
    }

    /**
     * Handle password login (optional secondary method).
     */
    public function loginWithPassword(Request $request)
    {
        // Verify reCAPTCHA
        $recaptcha = new RecaptchaService();
        $recaptchaResult = $recaptcha->verify($request->input('recaptcha_token'), 'login_password');
        
        if (!$recaptchaResult['success']) {
            return back()->withErrors(['email' => $recaptchaResult['error']])->withInput();
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $email = strtolower(trim($request->email));
        $member = PlatformMember::where('login_email_address', $email)->first();

        if (!$member || !$member->hashed_login_password) {
            return back()->withErrors(['email' => __translator('Invalid credentials or password not set.')]);
        }

        if (!Hash::check($request->password, $member->hashed_login_password)) {
            return back()->withErrors(['password' => __translator('Invalid password.')]);
        }

        Auth::login($member, $request->boolean('remember'));

        // Set active account
        $firstAccount = $member->tenant_accounts()
            ->wherePivot('membership_status', 'membership_active')
            ->first();

        if ($firstAccount) {
            session(['active_account_id' => $firstAccount->id]);
        }

        // Check for pending invitation
        if ($pendingToken = session('pending_invitation_token')) {
            session()->forget('pending_invitation_token');
            return redirect()->route('invitation.accept', ['token' => $pendingToken]);
        }

        return redirect()->route('home');
    }

    /**
     * Resend OTP code.
     */
    public function resendOtp()
    {
        $memberId = session('otp_member_id');
        $email = session('otp_email');

        if (!$email) {
            return redirect()->route('login-register')
                ->withErrors(['email' => __translator('Session expired. Please start again.')]);
        }

        // Try to find member by ID first, then by email
        $member = $memberId ? PlatformMember::find($memberId) : null;
        
        if (!$member) {
            // Fallback: find by email
            $member = PlatformMember::where('login_email_address', strtolower(trim($email)))->first();
        }

        if (!$member) {
            // Member truly doesn't exist - redirect back to send new OTP which will create them
            return redirect()->route('login-register')
                ->withInput(['email' => $email])
                ->with('status', __translator('Please submit your email again to receive a new code.'));
        }

        // Update session with correct member ID
        session(['otp_member_id' => $member->id]);

        // Generate new OTP
        $otpData = OneTimePasswordToken::createForMember($member);

        // Send OTP via email
        $this->sendOtpEmail($member, $otpData['plain_code'], false);

        return back()->with('status', __translator('A new verification code has been sent to your email.'));
    }

    /**
     * Send OTP email using the PlatformMailerService.
     * Reference: COMMON-PORTAL-MAILER-CODE-002.md
     */
    protected function sendOtpEmail(PlatformMember $member, string $code, bool $isNewMember): void
    {
        $mailer = new PlatformMailerService();
        
        $mailer->sendOtpEmail(
            recipientEmail: $member->login_email_address,
            code: $code,
            isNewMember: $isNewMember,
            recipientName: $member->full_name
        );
    }

    /**
     * Logout the member.
     */
    public function logout(Request $request)
    {
        // Preserve language preference across logout
        $preferredLanguage = session('preferred_language');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Restore language preference
        if ($preferredLanguage) {
            session(['preferred_language' => $preferredLanguage]);
        }

        return redirect()->route('home');
    }
}
