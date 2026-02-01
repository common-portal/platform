# Common Portal Platform — Branding Framework

**Version:** 1.0  
**Last Updated:** January 28, 2026

---

## Overview

The Common Portal Platform implements a **two-tier branding hierarchy** that enables:

1. **Platform-level branding** — Global defaults controlled by platform administrators
2. **Account-level branding** — White-label overrides for individual tenant accounts

**Hierarchy Rule:** Account branding overrides platform branding when accessed via custom subdomain.

---

## Architecture

### Data Storage

| Level | Storage | Model | Fields |
|-------|---------|-------|--------|
| **Platform** | `platform_settings` table | `PlatformSetting` | Key-value pairs (JSON-capable) |
| **Account** | `tenant_accounts` table | `TenantAccount` | Account-specific branding columns |

### Branding Resolution Logic

Located in: `src/app/Providers/ViewComposerServiceProvider.php`

```php
// Pseudocode
if (subdomain_tenant_exists) {
    brand_name = tenant_account.account_display_name
    brand_logo = tenant_account.branding_logo_image_path
} else {
    brand_name = PlatformSetting::getValue('platform_display_name')
    brand_logo = PlatformSetting::getValue('platform_logo_image_path')
}
```

---

## Platform-Level Branding

### Configuration Location

**Admin Panel:** `/administrator/platform-theme`

### Available Settings

| Setting Key | Type | Purpose | Default |
|-------------|------|---------|---------|
| `platform_display_name` | string | Brand name displayed in sidebar header | `"Common Portal"` |
| `platform_logo_image_path` | string | Logo image path (relative to `/storage/`) | `/images/platform-defaults/platform-logo.png` |
| `platform_favicon_image_path` | string | Browser tab icon | `/images/platform-defaults/favicon.png` |
| `social_sharing_preview_image_path` | string | Open Graph meta image (1200x630px recommended) | `/images/platform-defaults/meta-card-preview.png` |
| `social_sharing_meta_description` | string | Open Graph meta description | `"A white-label, multi-tenant portal platform."` |
| `active_theme_preset_name` | string | Selected theme preset (e.g., `dark-mode`, `light-orange`) | `null` |
| `custom_theme_color_overrides` | JSON | CSS variable overrides (see Theme Colors below) | `{}` |

### Theme Color Variables

Stored in `custom_theme_color_overrides` as JSON object:

```json
{
  "--sidebar-background-color": "#1e293b",
  "--sidebar-text-color": "#ffffff",
  "--sidebar-hover-background-color": "#334155",
  "--brand-primary-color": "#3b82f6",
  "--brand-secondary-color": "#8b5cf6",
  "--status-success-color": "#10b981",
  "--status-warning-color": "#f59e0b",
  "--status-error-color": "#ef4444",
  "--hyperlink-text-color": "#3b82f6",
  "--button-background-color": "#3b82f6",
  "--button-text-color": "#ffffff"
}
```

### Setting Values Programmatically

```php
use App\Models\PlatformSetting;

// Set brand name
PlatformSetting::setValue('platform_display_name', 'xRAMP.io');

// Set logo path
PlatformSetting::setValue('platform_logo_image_path', 'uploads/platform/logo.png');

// Set theme colors
PlatformSetting::setValue('custom_theme_color_overrides', [
    '--brand-primary-color' => '#e3be3b',
    '--sidebar-background-color' => '#1a1a1a',
]);

// Get values
$brandName = PlatformSetting::getValue('platform_display_name', 'Common Portal');
$brandName = PlatformSetting::getCached('platform_display_name'); // Cached version
```

### Environment Defaults

Set fallback defaults in `.env`:

```bash
APP_NAME="xRAMP.io"
DEFAULT_BRAND_NAME="xRAMP.io"
DEFAULT_BRAND_COLOR="#e3be3b"
```

---

## Account-Level Branding

### Configuration Location

**Account Settings:** `/account/settings`  
**Access:** Account owner or administrator

### Available Settings

| Database Column | Type | Purpose |
|----------------|------|---------|
| `account_display_name` | string | Account name (overrides platform name on subdomain) |
| `whitelabel_subdomain_slug` | string | Custom subdomain (e.g., `acme` → `acme.xramp.io`) |
| `branding_logo_image_path` | string | Account logo (overrides platform logo on subdomain) |

### Subdomain Detection

When a user accesses the platform via a custom subdomain:

1. Middleware detects subdomain from `Host` header
2. Looks up `tenant_accounts` table where `whitelabel_subdomain_slug` matches
3. Stores `subdomain_tenant_id` in session
4. View composer uses account branding if `subdomain_tenant_id` exists

### Setting Account Branding Programmatically

```php
use App\Models\TenantAccount;

$account = TenantAccount::find($accountId);

// Set account display name
$account->update([
    'account_display_name' => 'Acme Corporation',
]);

// Set custom subdomain
$account->update([
    'whitelabel_subdomain_slug' => 'acme',
]);

// Set account logo
$account->update([
    'branding_logo_image_path' => 'uploads/accounts/icons/acme-logo.png',
]);
```

---

## File Upload Guidelines

### Logo Files

| Type | Path | Constraints |
|------|------|-------------|
| **Platform Logo** | `storage/app/public/uploads/platform/` | JPEG, PNG, GIF, SVG, WebP · Max 2MB |
| **Account Logo** | `storage/app/public/uploads/accounts/icons/` | JPEG, PNG, GIF, SVG, WebP · Max 2MB |
| **Favicon** | `storage/app/public/uploads/platform/` | ICO, PNG (32x32 or 16x16) · Max 100KB |
| **Meta Card Image** | `storage/app/public/uploads/platform/` | JPEG, PNG (1200x630px) · Max 2MB |

### Filename Convention

```
{sanitized_name}_{hash_prefix}_{datetime}.{ext}
```

**Example:** `xramp-logo_a1b2c3d4_20260128_120000.png`

### Accessing Uploaded Files

```blade
{{-- Platform logo --}}
<img src="{{ asset('storage/' . $platformLogo) }}" alt="{{ $platformName }}">

{{-- Account logo (if subdomain tenant exists) --}}
@if($subdomainTenant && $subdomainTenant->branding_logo_image_path)
    <img src="{{ asset('storage/' . $subdomainTenant->branding_logo_image_path) }}" alt="Logo">
@endif
```

---

## View Variables

All layouts automatically receive branding variables via `ViewComposerServiceProvider`.

### Available in `layouts/platform.blade.php`

| Variable | Type | Source |
|----------|------|--------|
| `$platformName` | string | Account name (if subdomain) OR platform name |
| `$platformLogo` | string | Account logo (if subdomain) OR platform logo |
| `$favicon` | string | Platform favicon (always platform-level) |
| `$metaImage` | string | Open Graph meta image (always platform-level) |
| `$metaDescription` | string | Open Graph meta description (always platform-level) |
| `$themeColors` | array | Custom theme color overrides |
| `$subdomainTenant` | TenantAccount\|null | Current subdomain tenant (if any) |

### Usage Example

```blade
<head>
    <title>{{ $platformName }}</title>
    <link rel="icon" href="{{ asset($favicon) }}">
    <meta property="og:image" content="{{ asset($metaImage) }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    
    <style>
        :root {
            @foreach($themeColors as $variable => $value)
                {{ $variable }}: {{ $value }};
            @endforeach
        }
    </style>
</head>

<body>
    <div class="sidebar-header">
        <img src="{{ asset('storage/' . $platformLogo) }}" alt="{{ $platformName }}">
        <span>{{ $platformName }}</span>
    </div>
</body>
```

---

## Theme Presets

### Available Presets

| Preset Name | Description | Key Colors |
|-------------|-------------|------------|
| `dark-mode` | Dark backgrounds, light text | Sidebar: `#1e293b`, Primary: `#3b82f6` |
| `light-mode` | Light backgrounds, dark text | Sidebar: `#f8fafc`, Primary: `#3b82f6` |
| `grayscale` | Neutral gray tones | Sidebar: `#4b5563`, Primary: `#6b7280` |
| `dark-blue` | Professional blue theme | Sidebar: `#1e3a8a`, Primary: `#2563eb` |
| `light-orange` | Warm orange accents | Sidebar: `#fff7ed`, Primary: `#f97316` |
| `custom` | Start from scratch | User-defined |

### Applying a Theme Preset

```php
use App\Models\PlatformSetting;

PlatformSetting::setValue('active_theme_preset_name', 'dark-mode');

// Optionally override specific colors
PlatformSetting::setValue('custom_theme_color_overrides', [
    '--brand-primary-color' => '#e3be3b', // Gold
]);
```

---

## Branding Hierarchy Examples

### Example 1: Default Platform Access

**URL:** `https://xramp.io/dashboard`

| Setting | Value |
|---------|-------|
| Brand Name | `"xRAMP.io"` (from `platform_settings`) |
| Logo | `/storage/uploads/platform/xramp-logo.png` |
| Sidebar Colors | Platform theme colors |

### Example 2: Subdomain Tenant Access

**URL:** `https://acme.xramp.io/dashboard`

**Database:**
```
tenant_accounts.whitelabel_subdomain_slug = "acme"
tenant_accounts.account_display_name = "Acme Corporation"
tenant_accounts.branding_logo_image_path = "uploads/accounts/icons/acme-logo.png"
```

| Setting | Value |
|---------|-------|
| Brand Name | `"Acme Corporation"` (from `tenant_accounts`) |
| Logo | `/storage/uploads/accounts/icons/acme-logo.png` |
| Sidebar Colors | Platform theme colors (inherited) |
| Favicon | Platform favicon (inherited) |

---

## Rebranding Checklist

When rebranding the platform (e.g., from "Common Portal" to "xRAMP.io"):

### 1. Update Environment Files

```bash
# .env and .env.example
APP_NAME="xRAMP.io"
DEFAULT_BRAND_NAME="xRAMP.io"
DEFAULT_BRAND_COLOR="#e3be3b"
APP_URL=https://xramp.io
APP_BASE_DOMAIN=xramp.io
```

### 2. Update Platform Settings (Database)

```php
PlatformSetting::setValue('platform_display_name', 'xRAMP.io');
PlatformSetting::setValue('platform_logo_image_path', 'uploads/platform/xramp-logo.png');
PlatformSetting::setValue('custom_theme_color_overrides', [
    '--brand-primary-color' => '#e3be3b',
]);
```

### 3. Update Documentation Files

- `README.md` — Project title and description
- `COMMON-PORTAL-FRAMEWORK-README-002.md` — References to brand name
- `package.json` — Project name (optional)
- `composer.json` — Project name/description (optional)

### 4. Upload New Assets

- Platform logo: `storage/app/public/uploads/platform/xramp-logo.png`
- Favicon: `storage/app/public/uploads/platform/favicon.ico`
- Meta card image: `storage/app/public/uploads/platform/meta-card-preview.png`

### 5. Update Email Templates

```php
// config/mail.php
'from' => [
    'address' => 'noreply@xramp.io',
    'name' => env('APP_NAME', 'xRAMP.io'),
],
```

### 6. Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Custom Domain Setup (Account-Level)

For accounts wanting custom domains (e.g., `portal.acme.com`):

### DNS Configuration

**Option 1: CNAME Record**
```
CNAME: portal.acme.com → acme.xramp.io
```

**Option 2: A Record**
```
A: portal.acme.com → [server_ip_address]
```

### SSL Certificate

Use Certbot or similar to generate SSL certificate:

```bash
certbot certonly --webroot -w /var/www/html -d portal.acme.com
```

See `SSL-SETUP.md` for detailed instructions.

---

## API Integration

### Get Platform Branding (Public Endpoint)

```http
GET /api/branding/platform
```

**Response:**
```json
{
  "platform_name": "xRAMP.io",
  "platform_logo_url": "https://xramp.io/storage/uploads/platform/xramp-logo.png",
  "theme_colors": {
    "--brand-primary-color": "#e3be3b",
    "--sidebar-background-color": "#1e293b"
  }
}
```

### Get Account Branding (Authenticated Endpoint)

```http
GET /api/branding/account/{account_id}
Authorization: Bearer {api_token}
```

**Response:**
```json
{
  "account_name": "Acme Corporation",
  "subdomain_slug": "acme",
  "logo_url": "https://xramp.io/storage/uploads/accounts/icons/acme-logo.png"
}
```

---

## Best Practices

### Logo Dimensions

| Type | Recommended Size | Aspect Ratio |
|------|------------------|--------------|
| Platform Logo (Sidebar) | 200x50px | 4:1 (wide) |
| Account Logo | 200x50px | 4:1 (wide) |
| Favicon | 32x32px or 16x16px | 1:1 (square) |
| Open Graph Meta Image | 1200x630px | ~1.9:1 |

### Brand Name Guidelines

- **Platform Name:** Short, memorable (e.g., "xRAMP.io")
- **Account Name:** Company/organization name (e.g., "Acme Corporation")
- Avoid special characters that may break URLs/subdomains

### Color Accessibility

Ensure sufficient contrast ratios:
- **Text on background:** Minimum 4.5:1 ratio (WCAG AA)
- **Large text (18pt+):** Minimum 3:1 ratio
- **Interactive elements:** Minimum 3:1 ratio

Test with tools like [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/).

---

## Troubleshooting

### Branding Changes Not Appearing

1. **Clear caches:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

2. **Check session storage:**
   ```php
   // Verify subdomain tenant is detected
   dd(session('subdomain_tenant_id'));
   ```

3. **Verify database values:**
   ```php
   PlatformSetting::getValue('platform_display_name'); // Should return updated name
   ```

### Subdomain Not Resolving

1. **Check DNS records:**
   ```bash
   nslookup acme.xramp.io
   ```

2. **Verify wildcard DNS:**
   ```
   *.xramp.io → [server_ip]
   ```

3. **Check tenant account:**
   ```php
   TenantAccount::where('whitelabel_subdomain_slug', 'acme')->first();
   ```

### Theme Colors Not Applying

1. **Verify JSON format:**
   ```php
   $colors = PlatformSetting::getValue('custom_theme_color_overrides');
   dd(json_decode($colors, true)); // Should be valid array
   ```

2. **Check CSS variable syntax:**
   - Must start with `--`
   - Use lowercase with hyphens
   - Example: `--brand-primary-color` ✅ NOT `brandPrimaryColor` ❌

---

## Related Documentation

| Document | Purpose |
|----------|---------|
| `COMMON-PORTAL-FRAMEWORK-README-002.md` | Framework overview |
| `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md` | Complete feature specifications |
| `COMMON-PORTAL-DATABASE-SCHEMA-002.md` | Database schema details |
| `SSL-SETUP.md` | Custom domain SSL configuration |

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Jan 28, 2026 | Initial documentation of branding framework |

---

## License

MIT License — see [LICENSE](LICENSE) for details.
