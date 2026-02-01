# SSL/TLS Setup for xramp.io

This guide covers setting up Let's Encrypt SSL certificates for `xramp.io` and `*.xramp.io` using Certbot with DigitalOcean DNS authentication.

## Prerequisites

- Domain `xramp.io` registered and DNS managed by DigitalOcean
- DigitalOcean account with API access
- Docker and Docker Compose installed
- Port 80 and 443 accessible from the internet

## Why DigitalOcean DNS-01 Challenge?

For wildcard certificates (`*.xramp.io`), Let's Encrypt requires the DNS-01 challenge method. This involves:
- Creating a DNS TXT record to prove domain ownership
- DigitalOcean API integration automates this process
- No need to manually update DNS during renewal
- Works even if port 80 is blocked

## Setup Steps

### 1. Configure DigitalOcean API Token

1. Log into [DigitalOcean Control Panel](https://cloud.digitalocean.com/)
2. Go to **API** → **Tokens/Keys**
3. Click **Generate New Token**
4. Configure token:
   - **Name**: `letsencrypt-xramp` (or your choice)
   - **Scopes**: Enable both **Read** and **Write**
   - **Expiration**: No expiry (recommended for auto-renewal)
5. Copy the generated token immediately (can't view again)

### 2. Create DigitalOcean Credentials File

```bash
# Copy the example file
cp docker/digitalocean.ini.example certbot/digitalocean.ini

# Edit and add your API token
nano certbot/digitalocean.ini

# Secure the file (important!)
chmod 600 certbot/digitalocean.ini
```

### 3. Initialize SSL Certificates

```bash
# Make the init script executable
chmod +x scripts/init-letsencrypt.sh

# Edit the script to set your email
nano scripts/init-letsencrypt.sh
# Set: email="your-email@example.com"

# Run the initialization script
./scripts/init-letsencrypt.sh
```

**What this script does:**
1. Creates necessary directories (`certbot/conf`, `certbot/www`)
2. Downloads recommended TLS parameters from Certbot
3. Creates a temporary self-signed certificate for initial nginx startup
4. Starts nginx with the dummy certificate
5. Requests real certificates from Let's Encrypt using Cloudflare DNS-01
6. Replaces dummy certificates with real ones
7. Reloads nginx with new certificates

### 4. Start Production Environment

```bash
# Start all services with SSL
docker-compose -f docker-compose.ssl.yml up -d

# Check logs
docker-compose -f docker-compose.ssl.yml logs -f app
```

### 5. Verify SSL Installation

```bash
# Test HTTPS connection
curl -I https://xramp.io

# Check certificate details
openssl s_client -connect xramp.io:443 -servername xramp.io < /dev/null 2>/dev/null | openssl x509 -noout -text

# Verify wildcard subdomain
curl -I https://app.xramp.io
```

## Certificate Renewal

You have **two options** for automatic certificate renewal:

### Option 1: Docker Container Auto-Renewal (Recommended)

The `certbot` service in `docker-compose.ssl.yml` automatically checks for certificate renewal twice daily. Certificates are renewed when they have 30 days or less remaining.

**Pros:**
- Zero configuration after initial setup
- Always running with your application
- No system cron required

**Cons:**
- Requires containers to stay running
- Renewal depends on Docker service health

This option is **enabled by default** when you use `docker-compose.ssl.yml`.

### Option 2: System Cron Job

Use a system cron job for renewal (independent of Docker containers).

**Setup:**
```bash
# Run the setup script (one-time)
./scripts/setup-cron.sh
```

This configures a daily cron job at 3:00 AM that:
1. Runs the renewal check
2. Reloads nginx if certificates were renewed
3. Logs all output to `/var/log/letsencrypt-renew.log`

**Pros:**
- Independent of Docker container state
- More reliable for servers that restart often
- Centralized logging

**Cons:**
- Requires system cron access
- One more system service to manage

**View cron status:**
```bash
# List cron jobs
crontab -l

# View renewal logs
tail -f /var/log/letsencrypt-renew.log
```

### Manual Renewal

To manually renew certificates anytime:

```bash
# Using the renewal script
./scripts/renew-ssl.sh

# Or directly with docker-compose
docker-compose -f docker-compose.ssl.yml run --rm certbot renew
docker-compose -f docker-compose.ssl.yml exec app nginx -s reload
```

### Test Renewal (Dry Run)

Test renewal without actually renewing:

```bash
docker-compose -f docker-compose.ssl.yml run --rm certbot renew --dry-run
```

## Nginx SSL Configuration

The SSL nginx configuration (`docker/nginx-ssl.conf`) includes:

### Security Features
- **TLS 1.2 and 1.3** only (no older protocols)
- **Modern cipher suites** (Mozilla Intermediate configuration)
- **HSTS** with 1-year max-age and includeSubDomains
- **OCSP Stapling** for improved performance
- **Security headers**: X-Frame-Options, X-Content-Type-Options, CSP, etc.

### Performance Features
- **HTTP/2** support
- **Gzip compression** for text resources
- **Static asset caching** (1 year for images, CSS, JS)
- **SSL session caching**

### Certificate Locations
- **Certificate**: `/etc/letsencrypt/live/xramp.io/fullchain.pem`
- **Private Key**: `/etc/letsencrypt/live/xramp.io/privkey.pem`
- **Chain**: `/etc/letsencrypt/live/xramp.io/chain.pem`

## Testing SSL Configuration

### Online Tools
- [SSL Labs Server Test](https://www.ssllabs.com/ssltest/analyze.html?d=xramp.io)
- [Security Headers](https://securityheaders.com/?q=https://xramp.io)

### Command Line
```bash
# Test TLS versions
nmap --script ssl-enum-ciphers -p 443 xramp.io

# Check certificate expiry
echo | openssl s_client -servername xramp.io -connect xramp.io:443 2>/dev/null | openssl x509 -noout -dates
```

## Troubleshooting

### Certificate Request Failed

**Issue**: DNS-01 challenge fails
```bash
# Check DigitalOcean API token permissions
# Verify DNS is properly configured at DigitalOcean
dig TXT _acme-challenge.xramp.io

# Verify token has Read + Write access
# Test in staging mode first
# Edit init-letsencrypt.sh: staging=1
```

**Issue**: Rate limit hit
```bash
# Let's Encrypt has rate limits:
# - 50 certificates per registered domain per week
# - 5 duplicate certificates per week
# Use staging mode for testing (staging=1 in script)
```

### Nginx Won't Start

**Issue**: Certificate files don't exist
```bash
# Check if certificates exist
ls -la certbot/conf/live/xramp.io/

# If missing, run init script again
./scripts/init-letsencrypt.sh
```

**Issue**: Permission denied on certificate files
```bash
# Fix permissions
sudo chown -R root:root certbot/conf
sudo chmod 600 certbot/cloudflare.ini
```

### Renewal Fails

**Issue**: Certbot can't write to DNS
```bash
# Verify DigitalOcean credentials
docker-compose -f docker-compose.ssl.yml run --rm certbot renew --dry-run

# Check token hasn't expired or been revoked
# Verify token has both Read and Write scopes
# Regenerate token in DigitalOcean if needed
```

## File Structure

```
xramp.io/
├── certbot/
│   ├── conf/                      # Let's Encrypt certificates
│   │   └── live/xramp.io/
│   │       ├── fullchain.pem
│   │       ├── privkey.pem
│   │       └── chain.pem
│   ├── www/                       # ACME challenge files
│   └── digitalocean.ini           # DigitalOcean API credentials (gitignored)
├── docker/
│   ├── nginx-ssl.conf             # SSL-enabled nginx config
│   └── digitalocean.ini.example   # Template for API credentials
├── scripts/
│   ├── init-letsencrypt.sh        # Initial SSL setup
│   ├── renew-ssl.sh               # Manual renewal script
│   └── setup-cron.sh              # Configure cron auto-renewal
└── docker-compose.ssl.yml         # Production with SSL
```

## Security Checklist

- [ ] DigitalOcean API token has Read + Write permissions only
- [ ] `certbot/digitalocean.ini` has 600 permissions
- [ ] `certbot/digitalocean.ini` is added to `.gitignore`
- [ ] Email set in init script for renewal notifications
- [ ] Firewall allows ports 80 and 443
- [ ] HSTS header enabled (forces HTTPS)
- [ ] Certificate auto-renewal tested with `--dry-run`
- [ ] Auto-renewal configured (Docker or cron)
- [ ] Regular backups of `certbot/conf` directory

## Next Steps

After SSL is configured:
1. Update DNS records to point to your server
2. Configure any subdomains (api.xramp.io, app.xramp.io, etc.)
3. Set up monitoring for certificate expiry
4. Consider HSTS preloading: https://hstspreload.org/
5. Review Content Security Policy headers for your app

## Resources

- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
- [Certbot Documentation](https://eff-certbot.readthedocs.io/)
- [Certbot DigitalOcean Plugin](https://certbot-dns-digitalocean.readthedocs.io/)
- [DigitalOcean API Tokens](https://docs.digitalocean.com/reference/api/create-personal-access-token/)
- [Mozilla SSL Configuration Generator](https://ssl-config.mozilla.org/)
- [OWASP Transport Layer Protection](https://cheatsheetseries.owasp.org/cheatsheets/Transport_Layer_Protection_Cheat_Sheet.html)
