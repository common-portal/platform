# reCAPTCHA Domain Error Fix

## Issue
The login/register page shows: **"ERROR for site owner: Invalid domain"**

## Root Cause
The current reCAPTCHA keys were registered for a different domain (likely from the original common-portal-platform project). Google reCAPTCHA v3 validates that the domain making requests matches the registered domain(s).

## Current Configuration
```env
RECAPTCHA_SITE_KEY=6LeBPUEsAAAAAFaFlbbwDTIXnXFFMznHy75WjavO
RECAPTCHA_SECRET_KEY=6LeBPUEsAAAAAEQsWbQgXXpvVuJZyuA9tcF7DGi4
RECAPTCHA_ENABLED=true
RECAPTCHA_MINIMUM_SCORE=0.5
```

## Solution: Register New reCAPTCHA Keys for xramp.io

### Step 1: Go to reCAPTCHA Admin Console
Visit: https://www.google.com/recaptcha/admin

### Step 2: Create New Site
1. Click **"+"** to create a new site
2. **Label**: `xramp.io Production`
3. **reCAPTCHA type**: Select **reCAPTCHA v3**
4. **Domains**: Add the following domains:
   ```
   xramp.io
   *.xramp.io
   localhost (for local testing)
   ```
5. Accept the terms and click **Submit**

### Step 3: Copy New Keys
You'll receive:
- **Site Key** (public key - shown in frontend)
- **Secret Key** (private key - used in backend)

### Step 4: Update Environment Files
Update both `.env` files with the new keys:

**File: `/root/CascadeProjects/xramp.io/.env`**
```env
RECAPTCHA_SITE_KEY=your-new-site-key-here
RECAPTCHA_SECRET_KEY=your-new-secret-key-here
RECAPTCHA_ENABLED=true
RECAPTCHA_MINIMUM_SCORE=0.5
```

**File: `/root/CascadeProjects/xramp.io/src/.env`**
```env
RECAPTCHA_SITE_KEY=your-new-site-key-here
RECAPTCHA_SECRET_KEY=your-new-secret-key-here
RECAPTCHA_ENABLED=true
RECAPTCHA_MINIMUM_SCORE=0.5
```

### Step 5: Restart Application
```bash
cd /root/CascadeProjects/xramp.io
docker compose -f docker-compose.ssl.yml restart app
```

### Step 6: Clear Browser Cache
Hard refresh the page:
- **Windows/Linux**: `Ctrl + Shift + R`
- **macOS**: `Cmd + Shift + R`

## Temporary Workaround (Not Recommended)
If you need to disable reCAPTCHA temporarily:
```env
RECAPTCHA_ENABLED=false
```

**Note**: This removes bot protection from your login page.

## Verification
After updating the keys:
1. Visit https://xramp.io/login-register
2. The reCAPTCHA badge should show normally (small icon in bottom-right)
3. No error messages should appear
4. Form submission should work without issues

## Additional Notes
- reCAPTCHA v3 runs invisibly in the background
- The badge in the bottom-right is required by Google's terms
- The minimum score (0.5) determines how strict the bot detection is (0.0 = likely bot, 1.0 = likely human)
