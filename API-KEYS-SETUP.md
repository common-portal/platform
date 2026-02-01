# API Keys & Secrets Setup for xramp.io

## Overview

The `.env` file has been created with placeholder values for all required API keys and secrets. You need to add your actual credentials to make the application fully functional.

## Required API Keys

### 1. Mail/SMTP Credentials ✉️

**Used for:** OTP codes, user invitations, password resets, notifications

**Where to configure:** `.env` file
```bash
MAIL_HOST=mx.nsdb.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username        # ← ADD YOUR SMTP USERNAME
MAIL_PASSWORD=your-smtp-password-here   # ← ADD YOUR SMTP PASSWORD
MAIL_FROM_ADDRESS=noreply@xramp.io
```

**Providers:**
- **Current**: mx.nsdb.com (your existing SMTP server)
- **Alternatives**: Mailgun, SendGrid, Amazon SES, Postmark
- **Testing**: Use [Mailtrap](https://mailtrap.io/) for development

**Reference:** `.secrets.example/smtp.env`

---

### 2. OpenAI/Grok API Key 🤖

**Used for:** Translation service with AI fallback (TranslatorService.php)

**Where to configure:** `.env` file
```bash
OPENAI_API_KEY=sk-your-openai-api-key-here  # ← ADD YOUR API KEY
# OPENAI_MODEL=gpt-4o-mini                  # Optional: specify model
```

**Get your key:**
- **OpenAI**: https://platform.openai.com/api-keys
- **Grok xAI**: https://console.x.ai/

**Note:** The code uses Grok's `grok-4-1-fast-non-reasoning` model. If using OpenAI, the model name will need updating in `src/app/Services/TranslatorService.php`.

**Reference:** `.secrets.example/openai.env`

---

### 3. Google reCAPTCHA v3 🛡️

**Used for:** Login protection, spam prevention

**Where to configure:** `.env` file
```bash
RECAPTCHA_SITE_KEY=your-recaptcha-site-key-here      # ← ADD SITE KEY
RECAPTCHA_SECRET_KEY=your-recaptcha-secret-key-here  # ← ADD SECRET KEY
RECAPTCHA_ENABLED=true
RECAPTCHA_MINIMUM_SCORE=0.5
```

**Setup steps:**
1. Go to: https://www.google.com/recaptcha/admin
2. Register a new site:
   - **Label**: xramp.io
   - **Type**: reCAPTCHA v3
   - **Domains**: xramp.io, *.xramp.io, localhost (for testing)
3. Copy the **Site Key** and **Secret Key**
4. Add keys to `.env`

**Used in:** `src/app/Services/RecaptchaService.php`, login forms

---

### 4. Database Credentials 🗄️

**Where to configure:** `.env` file
```bash
DB_HOST=localhost              # ← Your PostgreSQL host
DB_PORT=5432
DB_DATABASE=xramp              # ← Your database name
DB_USERNAME=xramp              # ← Your database user
DB_PASSWORD=changeme           # ← ADD YOUR DATABASE PASSWORD
DB_SSLMODE=prefer
```

**Setup:**
- Use your existing DigitalOcean managed PostgreSQL
- Or set up a new database for xramp.io
- Run migrations after configuring: `docker compose -f docker-compose.ssl.yml exec app php artisan migrate`

---

## Files Created

### Main Configuration
- **`.env`** - Main environment file with all settings (update this with your keys)

### Templates (for reference)
- **`.secrets.example/`** - Example secrets files from common-portal-platform
  - `openai.env` - OpenAI/Grok API template
  - `smtp.env` - SMTP mail configuration template
  - `database.env` - Database configuration template

### Documentation
- **`API-KEYS-SETUP.md`** (this file)
- **`SSL-SETUP.md`** - SSL/TLS certificate setup guide

---

## What Happened to common-portal-platform Keys?

**Finding:** The common-portal-platform `.env` file only contained:
```bash
APP_KEY=base64:yRuF0ec314EY+wWgFFJFRhcFBsLPYrmVKldotByGcXE=
```

**No other API keys were found** in:
- `.env` file
- `.secrets/` directory (doesn't exist)
- Configuration files
- PHP code (only template/placeholder references)

**Conclusion:** The old platform was either:
1. Using placeholder/example values (not production-ready)
2. Keys were stored elsewhere (external secrets manager)
3. Keys need to be obtained fresh

---

## Security Best Practices

### ✅ Do's
- Keep `.env` file secure and never commit to git (already in `.gitignore`)
- Use strong, unique passwords
- Rotate API keys periodically
- Use separate keys for development/staging/production
- Enable 2FA on all service accounts

### ❌ Don'ts
- Never commit API keys to git
- Don't share keys via email/chat
- Don't hardcode keys in source code
- Don't use the same keys across environments

---

## Quick Setup Checklist

- [ ] Add SMTP credentials to `.env`
- [ ] Test email sending: `docker compose -f docker-compose.ssl.yml exec app php artisan tinker`
  ```php
  Mail::raw('Test', fn($m) => $m->to('your@email.com')->subject('Test'));
  ```
- [ ] Get OpenAI or Grok API key
- [ ] Add AI API key to `.env`
- [ ] Register reCAPTCHA v3 site
- [ ] Add reCAPTCHA keys to `.env`
- [ ] Configure database credentials
- [ ] Run database migrations
- [ ] Restart containers: `docker compose -f docker-compose.ssl.yml restart`
- [ ] Test application at https://xramp.io

---

## Restart After Configuration

After adding your API keys:

```bash
cd /root/CascadeProjects/xramp.io

# Restart containers to load new .env
docker compose -f docker-compose.ssl.yml restart

# Check logs
docker compose -f docker-compose.ssl.yml logs -f app

# Run migrations (first time only)
docker compose -f docker-compose.ssl.yml exec app php artisan migrate
```

---

## Support Resources

- **OpenAI Docs**: https://platform.openai.com/docs
- **Grok xAI Docs**: https://docs.x.ai/
- **reCAPTCHA Docs**: https://developers.google.com/recaptcha/docs/v3
- **Laravel Mail**: https://laravel.com/docs/mail
- **DigitalOcean Managed DB**: https://docs.digitalocean.com/products/databases/

---

## Current Status

✅ **SSL Certificates**: Active and auto-renewing  
✅ **Application**: Running on ports 80 & 443  
✅ **Configuration**: .env template created  
⚠️ **API Keys**: Need to be added (placeholders only)  
⚠️ **Database**: Needs credentials and migrations  
⚠️ **Email**: Needs SMTP configuration  

**Next steps:** Add your API keys to `.env` and restart the containers.
