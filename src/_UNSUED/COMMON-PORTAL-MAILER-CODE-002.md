# Mailer Framework - MX.NSDB.COM Gateway API

> **One endpoint. JSON POST. All emails routed through centralized mailer.**

---

## Quick Start

1. POST JSON to the API endpoint
2. Include required parameters
3. Receive success/error response

---

## API Endpoint

```
POST https://mx.nsdb.com:8443/common_mailer_gateway_api.php
Content-Type: application/json
```

---

## Parameters

### Required Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `mx_nsdb_com_username` | string | API authentication username |
| `email_to_emailaddress` | string | Recipient email address |
| `email_subject` | string | Email subject line |
| `email_html_message` | string | HTML body content |

### Optional Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `email_to_name` | string | Recipient display name |
| `email_from_name` | string | Sender display name (brand name) |
| `email_from_emailaddress` | string | Sender email address |
| `email_replyto_name` | string | Reply-to display name |
| `email_replyto_emailaddress` | string | Reply-to email address |
| `cc_email_addresses` | string | CC recipients (comma-separated) |
| `bcc_email_addresses` | string | BCC recipients (comma-separated) |

---

## cURL Example

```bash
curl -X POST https://mx.nsdb.com:8443/common_mailer_gateway_api.php \
  -H "Content-Type: application/json" \
  -d '{
    "mx_nsdb_com_username": "mailer@nsdb.com",
    "email_to_emailaddress": "user@example.com",
    "email_to_name": "John Doe",
    "email_from_emailaddress": "noreply@yourplatform.com",
    "email_from_name": "Your Platform",
    "email_subject": "Welcome to the Platform",
    "email_html_message": "<h1>Welcome!</h1><p>Thank you for signing up.</p>"
  }'
```

---

## PHP Implementation

```php
<?php
/**
 * MAILER FRAMEWORK - Single Function Implementation
 * Copy this function to send emails via MX.NSDB.COM gateway.
 */

define('MAILER_API_ENDPOINT', 'https://mx.nsdb.com:8443/common_mailer_gateway_api.php');
define('MAILER_API_USERNAME', 'mailer@nsdb.com');
define('MAILER_DEFAULT_FROM_NAME', 'Common Portal');
define('MAILER_DEFAULT_FROM_EMAIL', 'noreply@commonportal.com');

/**
 * Send email via MX.NSDB.COM gateway
 *
 * @param string $recipient_email_address  Required - recipient email
 * @param string $email_subject            Required - subject line
 * @param string $email_html_message       Required - HTML body
 * @param string $recipient_name           Optional - recipient name
 * @param string $from_name                Optional - sender name
 * @param string $from_email               Optional - sender email
 * @param string $reply_to_email           Optional - reply-to email
 * @param string $reply_to_name            Optional - reply-to name
 * @return array ['success' => bool, 'message' => string]
 */
function send_platform_email(
    string $recipient_email_address,
    string $email_subject,
    string $email_html_message,
    string $recipient_name = '',
    string $from_name = '',
    string $from_email = '',
    string $reply_to_email = '',
    string $reply_to_name = ''
): array {
    $payload = [
        'mx_nsdb_com_username'    => MAILER_API_USERNAME,
        'email_to_emailaddress'   => $recipient_email_address,
        'email_subject'           => $email_subject,
        'email_html_message'      => $email_html_message,
    ];

    // Add optional fields if provided
    if ($recipient_name)  $payload['email_to_name'] = $recipient_name;
    if ($from_name)       $payload['email_from_name'] = $from_name ?: MAILER_DEFAULT_FROM_NAME;
    if ($from_email)      $payload['email_from_emailaddress'] = $from_email ?: MAILER_DEFAULT_FROM_EMAIL;
    if ($reply_to_email)  $payload['email_replyto_emailaddress'] = $reply_to_email;
    if ($reply_to_name)   $payload['email_replyto_name'] = $reply_to_name;

    $ch = curl_init(MAILER_API_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'message' => 'cURL error: ' . $error];
    }

    if ($http_code >= 200 && $http_code < 300) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    }

    return ['success' => false, 'message' => 'API error: HTTP ' . $http_code];
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

### With Recipient Name
```php
$result = send_platform_email(
    'user@example.com',
    'Welcome to Common Portal',
    '<h1>Welcome, John!</h1><p>Your account has been created.</p>',
    'John Doe'
);
```

### Full Options
```php
$result = send_platform_email(
    recipient_email_address: 'user@example.com',
    email_subject: 'Team Invitation',
    email_html_message: '<p>You have been invited to join Acme Corp.</p>',
    recipient_name: 'Jane Smith',
    from_name: 'Acme Corp',
    from_email: 'noreply@acmecorp.com',
    reply_to_email: 'support@acmecorp.com',
    reply_to_name: 'Acme Support'
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
| **Password Reset** | "Reset your password" | Include reset link, expires in 1 hour |
| **Welcome Email** | "Welcome to {platform}" | Sent after email verification |

---

## Configuration Reference

| Constant | Description | Example |
|----------|-------------|---------|
| `MAILER_API_ENDPOINT` | Gateway API URL | `'https://mx.nsdb.com:8443/...'` |
| `MAILER_API_USERNAME` | API authentication | `'mailer@nsdb.com'` |
| `MAILER_DEFAULT_FROM_NAME` | Default sender name | `'Common Portal'` |
| `MAILER_DEFAULT_FROM_EMAIL` | Default sender email | `'noreply@commonportal.com'` |

---

## Summary

**What you get:**
- `send_platform_email()` - Single function for all email sending
- Centralized SMTP routing through MX.NSDB.COM
- JSON API with simple required/optional parameters
- Error handling with success/message response
- Configurable defaults for branding