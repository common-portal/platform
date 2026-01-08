# Mailer Framework - Direct SMTP to MX.NSDB.COM

> **Direct SMTP connection to mx.nsdb.com with STARTTLS authentication.**

---

## Quick Start

1. Connect to mx.nsdb.com:587 via SMTP
2. Authenticate with STARTTLS
3. Send email directly

---

## SMTP Configuration

| Setting | Value |
|---------|-------|
| **Host** | `mx.nsdb.com` |
| **Port** | `587` |
| **Encryption** | `STARTTLS` |
| **Username** | `common-portal@nsdb.com` |
| **Password** | `hmqR7$V@KLeJ%g90dJ6$FE%K$V5$M7jP` |

---

## Laravel .env Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=mx.nsdb.com
MAIL_PORT=587
MAIL_USERNAME=common-portal@nsdb.com
MAIL_PASSWORD="hmqR7$V@KLeJ%g90dJ6$FE%K$V5$M7jP"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="common-portal@nsdb.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## PHP Implementation (Direct SMTP)

```php
<?php
/**
 * MAILER FRAMEWORK - Direct SMTP to MX.NSDB.COM
 * Sends email directly via SMTP with STARTTLS authentication.
 */

define('SMTP_HOST', 'mx.nsdb.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'common-portal@nsdb.com');
define('SMTP_PASSWORD', 'hmqR7$V@KLeJ%g90dJ6$FE%K$V5$M7jP');
define('MAILER_DEFAULT_FROM_NAME', 'Common Portal');
define('MAILER_DEFAULT_FROM_EMAIL', 'common-portal@nsdb.com');

/**
 * Send email via direct SMTP to MX.NSDB.COM
 */
function send_platform_email(
    string $to_email,
    string $subject,
    string $html_message,
    string $to_name = '',
    string $from_name = '',
    string $from_email = '',
    string $reply_to_email = '',
    string $reply_to_name = '',
    string $cc = '',
    string $bcc = ''
): array {
    $from_name = $from_name ?: MAILER_DEFAULT_FROM_NAME;
    $from_email = $from_email ?: MAILER_DEFAULT_FROM_EMAIL;
    
    // Create SSL context
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    
    // Connect to SMTP server
    $smtp = @stream_socket_client(
        "tcp://" . SMTP_HOST . ":" . SMTP_PORT, 
        $errno, $errstr, 30, 
        STREAM_CLIENT_CONNECT, $context
    );
    
    if (!$smtp) {
        return ['success' => false, 'message' => "Connection failed: $errstr ($errno)"];
    }
    
    stream_set_timeout($smtp, 30);
    
    // Read greeting
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) != '220') {
        fclose($smtp);
        return ['success' => false, 'message' => "Server not ready: $response"];
    }
    
    // EHLO
    fwrite($smtp, "EHLO mx.nsdb.com\r\n");
    $response = '';
    while ($line = fgets($smtp, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') break;
    }
    
    // STARTTLS
    fwrite($smtp, "STARTTLS\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) != '220') {
        fclose($smtp);
        return ['success' => false, 'message' => "STARTTLS failed: $response"];
    }
    
    // Upgrade to TLS
    $crypto = stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
    if (!$crypto) {
        fclose($smtp);
        return ['success' => false, 'message' => "TLS upgrade failed"];
    }
    
    // EHLO again after TLS
    fwrite($smtp, "EHLO mx.nsdb.com\r\n");
    $response = '';
    while ($line = fgets($smtp, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') break;
    }
    
    // AUTH LOGIN
    fwrite($smtp, "AUTH LOGIN\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) != '334') {
        fclose($smtp);
        return ['success' => false, 'message' => "AUTH failed: $response"];
    }
    
    fwrite($smtp, base64_encode(SMTP_USERNAME) . "\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) != '334') {
        fclose($smtp);
        return ['success' => false, 'message' => "Username rejected: $response"];
    }
    
    fwrite($smtp, base64_encode(SMTP_PASSWORD) . "\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) != '235') {
        fclose($smtp);
        return ['success' => false, 'message' => "Authentication failed: $response"];
    }
    
    // MAIL FROM
    fwrite($smtp, "MAIL FROM:<$from_email>\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($smtp);
        return ['success' => false, 'message' => "MAIL FROM rejected: $response"];
    }
    
    // RCPT TO
    $recipients = array_map('trim', explode(',', $to_email));
    foreach ($recipients as $recipient) {
        if (empty($recipient)) continue;
        fwrite($smtp, "RCPT TO:<$recipient>\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) != '250') {
            fclose($smtp);
            return ['success' => false, 'message' => "RCPT TO rejected: $response"];
        }
    }
    
    // CC recipients
    if (!empty($cc)) {
        foreach (array_map('trim', explode(',', $cc)) as $cc_addr) {
            if (empty($cc_addr)) continue;
            fwrite($smtp, "RCPT TO:<$cc_addr>\r\n");
            fgets($smtp, 515);
        }
    }
    
    // BCC recipients
    if (!empty($bcc)) {
        foreach (array_map('trim', explode(',', $bcc)) as $bcc_addr) {
            if (empty($bcc_addr)) continue;
            fwrite($smtp, "RCPT TO:<$bcc_addr>\r\n");
            fgets($smtp, 515);
        }
    }
    
    // DATA
    fwrite($smtp, "DATA\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) != '354') {
        fclose($smtp);
        return ['success' => false, 'message' => "DATA rejected: $response"];
    }
    
    // Build message
    $boundary = md5(uniqid(time()));
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "From: \"$from_name\" <$from_email>\r\n";
    $headers .= "To: " . ($to_name ? "\"$to_name\" <{$recipients[0]}>" : $recipients[0]) . "\r\n";
    if (!empty($reply_to_email)) {
        $headers .= "Reply-To: " . ($reply_to_name ? "\"$reply_to_name\" <$reply_to_email>" : $reply_to_email) . "\r\n";
    }
    if (!empty($cc)) {
        $headers .= "Cc: $cc\r\n";
    }
    $headers .= "Subject: $subject\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "\r\n";
    
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= strip_tags($html_message) . "\r\n\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $html_message . "\r\n\r\n";
    $body .= "--$boundary--\r\n";
    
    fwrite($smtp, $headers . $body . "\r\n.\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($smtp);
        return ['success' => false, 'message' => "Message rejected: $response"];
    }
    
    // QUIT
    fwrite($smtp, "QUIT\r\n");
    fclose($smtp);
    
    return ['success' => true, 'message' => 'Email sent successfully'];
}
?>
```

---

## Usage Examples

### Basic Email
```php
$result = send_platform_email(
    'user@example.com',
    'Your OTP Code',
    '<p>Your verification code is: <strong>123456</strong></p>'
);
```

### With Reply-To
```php
$result = send_platform_email(
    to_email: 'user@example.com',
    subject: 'Support Request Received',
    html_message: '<p>We received your request and will respond soon.</p>',
    to_name: 'John Doe',
    from_name: 'Common Portal Support',
    reply_to_email: 'support@commonportal.com'
);

if ($result['success']) {
    echo "Email sent!";
} else {
    echo "Error: " . $result['message'];
}
```

---

## Common Use Cases

| Use Case | Subject Pattern | Notes |
|----------|-----------------|-------|
| **OTP Code** | "Your verification code" | Include code in HTML body |
| **Team Invitation** | "You've been invited to join {account}" | Include accept link |
| **Support Form** | "[Platform] {subject}" | Reply-to = submitter email |
| **Welcome Email** | "Welcome to {platform}" | Sent after registration |

---

## Configuration Reference

| Constant | Description | Value |
|----------|-------------|-------|
| `SMTP_HOST` | Mail server | `mx.nsdb.com` |
| `SMTP_PORT` | SMTP port | `587` |
| `SMTP_USERNAME` | Auth username | `common-portal@nsdb.com` |
| `SMTP_PASSWORD` | Auth password | `hmqR7$V@KLeJ%g90dJ6$FE%K$V5$M7jP` |
| `MAILER_DEFAULT_FROM_NAME` | Sender name | `Common Portal` |
| `MAILER_DEFAULT_FROM_EMAIL` | Sender email | `common-portal@nsdb.com` |

---

## Summary

- Direct SMTP to mx.nsdb.com:587 with STARTTLS
- No API wrapper needed
- `send_platform_email()` function handles full SMTP handshake
- Supports HTML + plain text multipart emails
- CC/BCC support included