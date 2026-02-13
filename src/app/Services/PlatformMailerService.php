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
        
        // Apply brand-specific SMTP credentials
        $this->applyBrandSmtpConfig();
    }

    /**
     * Apply brand-specific SMTP configuration.
     * When PROJECT_BRAND is 'directdebit', use directdebit.now SMTP credentials.
     * When PROJECT_BRAND is 'xramp', use xramp.io SMTP credentials.
     */
    protected function applyBrandSmtpConfig(): void
    {
        $brand = config('app.project_brand', 'common');
        
        if ($brand === 'directdebit') {
            config([
                'mail.mailers.smtp.username' => 'support@directdebit.now',
                'mail.mailers.smtp.password' => 'G4L)F1!0FfNAq64bYFKXvzkSF24EM3h3',
                'mail.from.address' => 'support@directdebit.now',
            ]);
            $this->defaultFromEmail = 'support@directdebit.now';
        } elseif ($brand === 'xramp') {
            config([
                'mail.mailers.smtp.username' => 'support@xramp.io',
                'mail.mailers.smtp.password' => '5!Bkhwg6LXszH6UKKvZ!BHy11YxK$(tY',
                'mail.from.address' => 'support@xramp.io',
            ]);
            $this->defaultFromEmail = 'support@xramp.io';
        }
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
            ? __translator("Welcome to {$platformName}! Your account has been created.")
            : __translator("You requested a verification code to log in to {$platformName}.");
        
        $codeLabel = __translator('Your verification code is:');
        $expiresText = __translator('This code expires in 72 hours.');
        $ignoreText = __translator("If you didn't request this code, you can safely ignore this email.");

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
        
        <p>{$welcomeText}</p>
        
        <p>{$codeLabel}</p>
        
        <div style="background-color: #0f0f1a; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #00ff88;">{$code}</span>
        </div>
        
        <p style="opacity: 0.7; font-size: 14px;">{$expiresText}</p>
        
        <hr style="border: none; border-top: 1px solid #2a2a4e; margin: 30px 0;">
        
        <p style="opacity: 0.5; font-size: 12px; margin: 0;">
            {$ignoreText}
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
        $inviteText = __translator("{$inviterName} has invited you to join {$accountName}.");
        $clickText = __translator('Click the button below to accept the invitation:');
        $acceptText = __translator('Accept Invitation');
        $copyText = __translator('Or copy this link:');
        $ignoreText = __translator("If you weren't expecting this invitation, you can safely ignore this email.");
        
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
        
        <p>{$inviteText}</p>
        
        <p style="margin-bottom: 30px;">{$clickText}</p>
        
        <p style="font-size: 18px; margin: 30px 0;">
            <a href="{$acceptUrl}" style="color: #00ff88; text-decoration: underline; font-weight: 600;">➜ {$acceptText}</a>
        </p>
        
        <p style="opacity: 0.7; font-size: 14px; margin-top: 30px;">{$copyText}</p>
        <p style="opacity: 0.7; font-size: 14px;"><a href="{$acceptUrl}" style="color: #3b82f6;">{$acceptUrl}</a></p>
        
        <hr style="border: none; border-top: 1px solid #2a2a4e; margin: 30px 0;">
        
        <p style="opacity: 0.5; font-size: 12px; margin: 0;">
            {$ignoreText}
        </p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Send payment received notification email.
     */
    public function sendPaymentReceivedEmail(
        string $recipientEmail,
        string $recipientName,
        string $accountName,
        string $amount,
        string $currencyCode,
        string $ibanNumber,
        string $senderName,
        string $transactionDateTime
    ): array {
        $platformName = $this->defaultFromName;
        $subject = "Payment Received: {$currencyCode} {$amount}";

        $htmlMessage = $this->buildPaymentReceivedEmailHtml(
            $accountName,
            $amount,
            $currencyCode,
            $ibanNumber,
            $senderName,
            $transactionDateTime,
            $platformName
        );

        return $this->send(
            recipientEmail: $recipientEmail,
            subject: $subject,
            htmlMessage: $htmlMessage,
            recipientName: $recipientName
        );
    }

    /**
     * Build HTML for payment received email.
     */
    protected function buildPaymentReceivedEmailHtml(
        string $accountName,
        string $amount,
        string $currencyCode,
        string $ibanNumber,
        string $senderName,
        string $transactionDateTime,
        string $platformName
    ): string {
        $headerText = __translator('Payment Received');
        $introText = __translator("A payment has been received into your account.");
        $accountLabel = __translator('Account');
        $amountLabel = __translator('Amount');
        $senderLabel = __translator('Sender');
        $ibanLabel = __translator('IBAN');
        $dateLabel = __translator('Date/Time');
        $footerText = __translator('This is an automated notification. Please log in to your account to view full transaction details.');

        // Mask IBAN for security (show last 4 digits)
        $maskedIban = '****' . substr($ibanNumber, -4);

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
        
        <h2 style="color: #00ff88; margin: 0 0 20px 0; font-size: 20px;">✓ {$headerText}</h2>
        
        <p>{$introText}</p>
        
        <div style="background-color: #0f0f1a; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; opacity: 0.7;">{$accountLabel}:</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: 600;">{$accountName}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; opacity: 0.7;">{$amountLabel}:</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: 600; color: #00ff88; font-size: 18px;">{$currencyCode} {$amount}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; opacity: 0.7;">{$senderLabel}:</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: 500;">{$senderName}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; opacity: 0.7;">{$ibanLabel}:</td>
                    <td style="padding: 8px 0; text-align: right; font-family: monospace;">{$maskedIban}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; opacity: 0.7;">{$dateLabel}:</td>
                    <td style="padding: 8px 0; text-align: right;">{$transactionDateTime}</td>
                </tr>
            </table>
        </div>
        
        <hr style="border: none; border-top: 1px solid #2a2a4e; margin: 30px 0;">
        
        <p style="opacity: 0.5; font-size: 12px; margin: 0;">
            {$footerText}
        </p>
    </div>
</body>
</html>
HTML;
    }
}
