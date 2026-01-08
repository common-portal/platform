<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\PlatformSetting;

/**
 * MAILER FRAMEWORK - Laravel Mail + SMTP
 * 
 * Uses Laravel's built-in Mail system with SMTP config from .env
 * Easy to change credentials: just update MAIL_* variables in .env
 */
class PlatformMailerService
{
    protected string $defaultFromName;
    protected string $defaultFromEmail;

    public function __construct()
    {
        $this->defaultFromName = PlatformSetting::getValue('platform_display_name', config('app.name', 'Common Portal'));
        $this->defaultFromEmail = config('mail.from.address', 'noreply@commonportal.com');
    }

    /**
     * Send email via Laravel Mail (SMTP configured in .env)
     *
     * @param string $recipientEmail Required - recipient email
     * @param string $subject Required - subject line
     * @param string $htmlMessage Required - HTML body
     * @param string $recipientName Optional - recipient name
     * @param string $fromName Optional - sender name
     * @param string $fromEmail Optional - sender email
     * @param string $replyToEmail Optional - reply-to email
     * @param string $replyToName Optional - reply-to name
     * @return array ['success' => bool, 'message' => string]
     */
    public function send(
        string $recipientEmail,
        string $subject,
        string $htmlMessage,
        string $recipientName = '',
        string $fromName = '',
        string $fromEmail = '',
        string $replyToEmail = '',
        string $replyToName = ''
    ): array {
        try {
            $fromName = $fromName ?: $this->defaultFromName;
            $fromEmail = $fromEmail ?: $this->defaultFromEmail;

            Mail::html($htmlMessage, function ($message) use ($recipientEmail, $recipientName, $subject, $fromName, $fromEmail, $replyToEmail, $replyToName) {
                $message->to($recipientEmail, $recipientName ?: null)
                    ->subject($subject)
                    ->from($fromEmail, $fromName);

                if ($replyToEmail) {
                    $message->replyTo($replyToEmail, $replyToName ?: null);
                }
            });

            Log::info('PlatformMailer sent', ['recipient' => $recipientEmail, 'subject' => $subject]);
            return ['success' => true, 'message' => 'Email sent successfully'];

        } catch (\Exception $e) {
            Log::error('PlatformMailer error', ['error' => $e->getMessage(), 'recipient' => $recipientEmail]);
            return ['success' => false, 'message' => 'Mail error: ' . $e->getMessage()];
        }
    }

    /**
     * Send OTP verification email.
     */
    public function sendOtpEmail(string $recipientEmail, string $code, bool $isNewMember = false, string $recipientName = ''): array
    {
        $platformName = $this->defaultFromName;
        $subject = "PIN: " . $code;

        $htmlMessage = $this->buildOtpEmailHtml($code, $isNewMember, $platformName);

        return $this->send(
            recipientEmail: $recipientEmail,
            subject: $subject,
            htmlMessage: $htmlMessage,
            recipientName: $recipientName
        );
    }

    /**
     * Send team invitation email.
     */
    public function sendInvitationEmail(
        string $recipientEmail,
        string $inviterName,
        string $accountName,
        string $acceptUrl
    ): array {
        $platformName = $this->defaultFromName;
        $subject = "You've been invited to join {$accountName}";

        $htmlMessage = $this->buildInvitationEmailHtml($inviterName, $accountName, $acceptUrl, $platformName);

        return $this->send(
            recipientEmail: $recipientEmail,
            subject: $subject,
            htmlMessage: $htmlMessage
        );
    }

    /**
     * Build HTML for OTP email.
     */
    protected function buildOtpEmailHtml(string $code, bool $isNewMember, string $platformName): string
    {
        $welcomeText = $isNewMember
            ? "<p>Welcome to {$platformName}! Your account has been created.</p>"
            : "<p>You requested a verification code to log in to {$platformName}.</p>";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #0f0f1a; color: #e0e0e0; padding: 40px 20px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background-color: #1a1a2e; border-radius: 8px; padding: 40px;">
        <h1 style="color: #00ff88; margin: 0 0 20px 0; font-size: 24px;">{$platformName}</h1>
        
        {$welcomeText}
        
        <p>Your verification code is:</p>
        
        <div style="background-color: #0f0f1a; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #00ff88;">{$code}</span>
        </div>
        
        <p style="opacity: 0.7; font-size: 14px;">This code expires in 72 hours.</p>
        
        <hr style="border: none; border-top: 1px solid #2a2a4e; margin: 30px 0;">
        
        <p style="opacity: 0.5; font-size: 12px; margin: 0;">
            If you didn't request this code, you can safely ignore this email.
        </p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Build HTML for invitation email.
     */
    protected function buildInvitationEmailHtml(string $inviterName, string $accountName, string $acceptUrl, string $platformName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #0f0f1a; color: #e0e0e0; padding: 40px 20px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background-color: #1a1a2e; border-radius: 8px; padding: 40px;">
        <h1 style="color: #00ff88; margin: 0 0 20px 0; font-size: 24px;">{$platformName}</h1>
        
        <p><strong>{$inviterName}</strong> has invited you to join <strong>{$accountName}</strong>.</p>
        
        <p>Click the button below to accept the invitation:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$acceptUrl}" style="display: inline-block; background-color: #00ff88; color: #1a1a2e; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 600;">
                Accept Invitation
            </a>
        </div>
        
        <p style="opacity: 0.7; font-size: 14px;">Or copy this link: <a href="{$acceptUrl}" style="color: #3b82f6;">{$acceptUrl}</a></p>
        
        <hr style="border: none; border-top: 1px solid #2a2a4e; margin: 30px 0;">
        
        <p style="opacity: 0.5; font-size: 12px; margin: 0;">
            If you weren't expecting this invitation, you can safely ignore this email.
        </p>
    </div>
</body>
</html>
HTML;
    }
}
