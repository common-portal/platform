<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\PlatformSetting;

/**
 * MAILER FRAMEWORK - MX.NSDB.COM Gateway API
 * Reference: COMMON-PORTAL-MAILER-CODE-002.md
 */
class PlatformMailerService
{
    protected string $apiEndpoint = 'https://mx.nsdb.com:8443/common_mailer_gateway_api.php';
    protected string $apiUsername = 'mailer@nsdb.com';
    protected string $defaultFromName;
    protected string $defaultFromEmail = 'noreply@commonportal.com';

    public function __construct()
    {
        $this->defaultFromName = PlatformSetting::getValue('platform_display_name', 'Common Portal');
    }

    /**
     * Send email via MX.NSDB.COM gateway
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
        $payload = [
            'mx_nsdb_com_username' => $this->apiUsername,
            'email_to_emailaddress' => $recipientEmail,
            'email_subject' => $subject,
            'email_html_message' => $htmlMessage,
            'email_from_name' => $fromName ?: $this->defaultFromName,
            'email_from_emailaddress' => $fromEmail ?: $this->defaultFromEmail,
        ];

        if ($recipientName) {
            $payload['email_to_name'] = $recipientName;
        }
        if ($replyToEmail) {
            $payload['email_replyto_emailaddress'] = $replyToEmail;
        }
        if ($replyToName) {
            $payload['email_replyto_name'] = $replyToName;
        }

        try {
            $ch = curl_init($this->apiEndpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($payload),
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('PlatformMailer cURL error', ['error' => $error, 'recipient' => $recipientEmail]);
                return ['success' => false, 'message' => 'cURL error: ' . $error];
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                Log::info('PlatformMailer sent', ['recipient' => $recipientEmail, 'subject' => $subject]);
                return ['success' => true, 'message' => 'Email sent successfully'];
            }

            Log::error('PlatformMailer API error', ['http_code' => $httpCode, 'recipient' => $recipientEmail]);
            return ['success' => false, 'message' => 'API error: HTTP ' . $httpCode];

        } catch (\Exception $e) {
            Log::error('PlatformMailer exception', ['error' => $e->getMessage(), 'recipient' => $recipientEmail]);
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Send OTP verification email.
     */
    public function sendOtpEmail(string $recipientEmail, string $code, bool $isNewMember = false, string $recipientName = ''): array
    {
        $platformName = $this->defaultFromName;
        $subject = $isNewMember 
            ? "Welcome to {$platformName}! Your verification code" 
            : "Your {$platformName} verification code";

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
