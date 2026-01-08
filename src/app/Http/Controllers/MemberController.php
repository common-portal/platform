<?php

namespace App\Http\Controllers;

use App\Models\OneTimePasswordToken;
use App\Services\PlatformMailerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    /**
     * Show member settings page.
     */
    public function showSettings()
    {
        return view('pages.member.settings');
    }

    /**
     * Update profile (name).
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'member_first_name' => 'nullable|string|max:100',
            'member_last_name' => 'nullable|string|max:100',
        ]);

        auth()->user()->update([
            'member_first_name' => $request->member_first_name,
            'member_last_name' => $request->member_last_name,
        ]);

        return back()->with('status', 'Profile updated successfully.');
    }

    /**
     * Request email change - sends OTP to new email.
     */
    public function requestEmailChange(Request $request)
    {
        $request->validate([
            'new_email' => 'required|email|max:255|unique:platform_members,login_email_address',
        ]);

        $newEmail = strtolower(trim($request->new_email));

        // Store pending email in session
        session(['pending_email_change' => $newEmail]);

        // Invalidate any existing tokens
        OneTimePasswordToken::invalidateAllForMember(auth()->id());

        // Create OTP token
        $result = OneTimePasswordToken::createForMember(auth()->user());

        // Send OTP to new email
        $mailer = new PlatformMailerService();
        $mailer->sendOtpEmail($newEmail, $result['plain_code']);

        return redirect()->route('member.settings.email.verify')
            ->with('status', "Verification code sent to {$newEmail}");
    }

    /**
     * Show email verification form.
     */
    public function showEmailVerifyForm()
    {
        $pendingEmail = session('pending_email_change');

        if (!$pendingEmail) {
            return redirect()->route('member.settings')
                ->withErrors(['email' => 'No pending email change.']);
        }

        return view('pages.member.verify-email', [
            'pendingEmail' => $pendingEmail,
        ]);
    }

    /**
     * Verify OTP and complete email change.
     */
    public function verifyEmailChange(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $pendingEmail = session('pending_email_change');

        if (!$pendingEmail) {
            return redirect()->route('member.settings')
                ->withErrors(['email' => 'No pending email change.']);
        }

        // Find valid token
        $token = OneTimePasswordToken::where('platform_member_id', auth()->id())
            ->whereNull('token_used_at_timestamp')
            ->where('token_expires_at_timestamp', '>', now())
            ->orderBy('created_at_timestamp', 'desc')
            ->first();

        if (!$token || !$token->verifyCode($request->code)) {
            return back()->withErrors(['code' => 'Invalid or expired code.']);
        }

        // Update email
        auth()->user()->update([
            'login_email_address' => $pendingEmail,
        ]);

        // Mark token as used
        $token->markAsUsed();

        // Clear session
        session()->forget('pending_email_change');

        return redirect()->route('member.settings')
            ->with('status', 'Email address updated successfully.');
    }

    /**
     * Set or update password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        auth()->user()->update([
            'hashed_login_password' => Hash::make($request->password),
        ]);

        return back()->with('password_status', 'Password set successfully.');
    }

    /**
     * Remove password (revert to OTP-only).
     */
    public function removePassword()
    {
        auth()->user()->update([
            'hashed_login_password' => null,
        ]);

        return back()->with('password_status', 'Password removed. You can still log in with OTP.');
    }

    /**
     * Update language preference.
     */
    public function updateLanguage(Request $request)
    {
        $request->validate([
            'language_code' => 'required|string|size:2',
        ]);

        auth()->user()->update([
            'preferred_language_code' => $request->language_code,
        ]);

        session(['preferred_language' => $request->language_code]);

        return back()->with('language_status', 'Language preference updated.');
    }

    /**
     * Upload profile avatar.
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
        ]);

        $member = auth()->user();
        $file = $request->file('avatar');
        
        // Generate filename: original_memberhash_datetime.extension
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalName = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalName); // Sanitize
        $extension = $file->getClientOriginalExtension();
        $memberHash = substr($member->record_unique_identifier, 0, 8);
        $datetime = now()->format('Ymd_His');
        
        $filename = "{$originalName}_{$memberHash}_{$datetime}.{$extension}";
        
        // Store in public/uploads/members/icons
        $path = $file->storeAs('uploads/members/icons', $filename, 'public');
        
        // Delete old avatar if exists
        if ($member->profile_avatar_image_path) {
            Storage::disk('public')->delete($member->profile_avatar_image_path);
        }
        
        // Update member
        $member->update([
            'profile_avatar_image_path' => $path,
        ]);

        return back()->with('status', 'Profile photo updated successfully.');
    }

    /**
     * Remove profile avatar.
     */
    public function removeAvatar()
    {
        $member = auth()->user();
        
        if ($member->profile_avatar_image_path) {
            Storage::disk('public')->delete($member->profile_avatar_image_path);
            $member->update(['profile_avatar_image_path' => null]);
        }

        return back()->with('status', 'Profile photo removed.');
    }
}
