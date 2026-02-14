# XRAMP.IO — Server Reboot Recovery Guide

> **AI CONTEXT:** This document explains how to restore xramp.io after a server reboot. The server runs multiple COMMON-PORTAL instances that may conflict with each other.

---

## Quick Recovery Checklist

After server reboot, run these commands in order:

```bash
# 1. Navigate to xramp.io project
cd /root/CascadeProjects/xramp.io

# 2. Start Docker containers
docker compose up -d

# 3. Verify database has tables (should show 35+ tables)
docker exec platform-postgres psql -U xramp -d xramp -c "\dt"

# 4. If database is empty, see "Database Issue" section below

# 5. Reload nginx (may already have correct config)
sudo systemctl reload nginx

# 6. Test site
curl -I https://xramp.io
```

---

## Known Issues After Reboot

### Issue 1: Database Tables Missing

**Symptoms:**
- Site shows 500 error
- Laravel logs show: `SQLSTATE[42P01]: Undefined table: relation "platform_settings" does not exist`

**Cause:**
The `docker-compose.yml` may start with wrong database volume. There are two volumes:
- `xrampio_pgdata` (empty/wrong)
- `xrampio_xramp_pgdata` (correct - contains all your data)

**Fix:**
Ensure `docker-compose.yml` has this configuration:

```yaml
# In postgres service section:
volumes:
  - xramp_pgdata:/var/lib/postgresql/data

# In volumes section at bottom:
volumes:
  xramp_pgdata:
    external: true
    name: xrampio_xramp_pgdata
```

Then restart:
```bash
docker compose down
docker compose up -d
```

---

### Issue 2: Nginx Configuration Missing

**Symptoms:**
- Browser shows SSL certificate error: `NET::ERR_CERT_COMMON_NAME_INVALID`
- Certificate shows `common-portal.mondax.co` instead of `xramp.io`

**Cause:**
Host nginx doesn't have xramp.io configuration. Only `common-portal.mondax.co` is configured.

**Fix:**
```bash
# 1. Copy nginx config from project to host
sudo cp /root/CascadeProjects/xramp.io/docker/nginx-ssl.conf /etc/nginx/sites-available/xramp.io

# 2. Create symlink in sites-enabled
sudo ln -sf /etc/nginx/sites-available/xramp.io /etc/nginx/sites-enabled/xramp.io

# 3. Copy SSL certificates
sudo mkdir -p /etc/letsencrypt/live/xramp.io
sudo mkdir -p /etc/letsencrypt/archive/xramp.io
sudo cp -r /root/CascadeProjects/xramp.io/certbot/conf/live/xramp.io/* /etc/letsencrypt/live/xramp.io/
sudo cp -r /root/CascadeProjects/xramp.io/certbot/conf/archive/xramp.io/* /etc/letsencrypt/archive/xramp.io/

# 4. Test nginx config
sudo nginx -t

# 5. Reload nginx
sudo systemctl reload nginx
```

**Note:** If nginx -t fails with `unknown directive "http2"`:
- Edit `/etc/nginx/sites-available/xramp.io`
- Change `http2 on;` to put `http2` on the listen line: `listen 443 ssl http2;`
- This is due to nginx version (1.24 vs 1.25+ syntax)

---

### Issue 3: Port Conflicts with Other COMMON-PORTAL Instances

**Symptoms:**
- Docker containers fail to start
- Port 8080 or 5432 already in use

**Cause:**
Another COMMON-PORTAL project (e.g., common-portal.mondax.co) may be running on same ports.

**Fix:**
```bash
# Check what's using port 8080
sudo ss -tlnp | grep 8080

# Stop conflicting containers (if common-portal is running)
cd /root/CascadeProjects/common-portal  # or wherever it is
docker compose down

# Then start xramp.io
cd /root/CascadeProjects/xramp.io
docker compose up -d
```

---

## Architecture Reference

### Docker Containers (xramp.io)
| Container | Image | Port | Purpose |
|-----------|-------|------|---------|
| platform-app | xrampio-app | 8080→80 | Laravel app with nginx+php-fpm |
| platform-postgres | postgres:16-alpine | 5432 | PostgreSQL database |
| platform-redis | redis:7-alpine | 6379 | Cache/sessions |
| platform-queue | xrampio-queue | - | Queue worker |
| platform-adminer | adminer | 8080 (internal) | Database admin UI |
| xramp-certbot | certbot | - | SSL certificate management |

### Key Paths
| Path | Purpose |
|------|---------|
| `/root/CascadeProjects/xramp.io/` | Project root |
| `/root/CascadeProjects/xramp.io/src/` | Laravel application |
| `/root/CascadeProjects/xramp.io/docker/nginx-ssl.conf` | Nginx config template |
| `/root/CascadeProjects/xramp.io/certbot/conf/` | SSL certificates |
| `/etc/nginx/sites-available/xramp.io` | Host nginx config (copy from project) |
| `/etc/letsencrypt/live/xramp.io/` | SSL certs (copy from certbot/conf/) |

### Database Connection
| Setting | Value |
|---------|-------|
| Host | postgres (Docker network) |
| Port | 5432 |
| Database | xramp |
| Username | xramp |
| Password | secret |
| Volume | xrampio_xramp_pgdata |

---

## Full Recovery Script

Save this as a script if needed:

```bash
#!/bin/bash
# XRAMP.IO Server Recovery Script

echo "=== XRAMP.IO Recovery ==="

cd /root/CascadeProjects/xramp.io

echo "1. Starting Docker containers..."
docker compose up -d

echo "2. Waiting for database..."
sleep 5

echo "3. Checking database tables..."
docker exec platform-postgres psql -U xramp -d xramp -c "\dt" | head -10

echo "4. Ensuring nginx config exists..."
if [ ! -f /etc/nginx/sites-available/xramp.io ]; then
    echo "   Copying nginx config..."
    cp docker/nginx-ssl.conf /etc/nginx/sites-available/xramp.io
    ln -sf /etc/nginx/sites-available/xramp.io /etc/nginx/sites-enabled/xramp.io
fi

echo "5. Ensuring SSL certs exist..."
if [ ! -d /etc/letsencrypt/live/xramp.io ]; then
    echo "   Copying SSL certificates..."
    mkdir -p /etc/letsencrypt/live/xramp.io
    mkdir -p /etc/letsencrypt/archive/xramp.io
    cp -r certbot/conf/live/xramp.io/* /etc/letsencrypt/live/xramp.io/
    cp -r certbot/conf/archive/xramp.io/* /etc/letsencrypt/archive/xramp.io/
fi

echo "6. Reloading nginx..."
systemctl reload nginx

echo "7. Testing site..."
curl -I https://xramp.io 2>&1 | head -5

echo "=== Recovery Complete ==="
```

---

## Verification Commands

```bash
# Check Docker containers
docker ps --format "table {{.Names}}\t{{.Status}}"

# Check database connection
docker exec platform-postgres psql -U xramp -d xramp -c "SELECT COUNT(*) FROM transactions;"

# Check nginx status
systemctl status nginx

# Test HTTPS
curl -I https://xramp.io

# View app logs
docker logs platform-app --tail 50

# View Laravel logs
docker exec platform-app cat /var/www/html/storage/logs/laravel.log | tail -50
```

---

*Last Updated: February 1, 2026*
*Issue Reference: Server reboot caused database volume mismatch and missing nginx configuration*
