# COMMON-PORTAL — Server Reboot / Revert Guide

> **AI CONTEXT:** This document explains how to REVERT from xramp.io back to common-portal, or how to restore common-portal after a server reboot. The server runs multiple COMMON-PORTAL instances that share ports and may conflict.

---

## Understanding the Conflict

**xramp.io** and **common-portal.mondax.co** are both based on the COMMON-PORTAL framework and use the same ports:

| Resource | Port | Conflict |
|----------|------|----------|
| Docker App | 8080 | ⚠️ Only one can run |
| PostgreSQL | 5432 | ⚠️ Only one can run |
| Redis | 6379 | ⚠️ Only one can run |
| Host Nginx | 80/443 | Can serve multiple domains |

**You can only run ONE project's Docker containers at a time.**

---

## Switching FROM xramp.io TO common-portal

### Step 1: Stop xramp.io Docker Containers

```bash
cd /root/CascadeProjects/xramp.io
docker compose down
```

### Step 2: Start common-portal Docker Containers

```bash
cd /root/CascadeProjects/common-portal  # Adjust path if different
docker compose up -d
```

### Step 3: Update Host Nginx

The host nginx needs to proxy to the correct Docker container. If common-portal was working before, its nginx config should already exist.

```bash
# Check if common-portal nginx config exists
ls -la /etc/nginx/sites-enabled/

# Should see: common-portal.mondax.co -> /etc/nginx/sites-available/common-portal.mondax.co

# If xramp.io is enabled but you want common-portal only:
# (Optional) Remove xramp.io from sites-enabled
sudo rm /etc/nginx/sites-enabled/xramp.io

# Reload nginx
sudo systemctl reload nginx
```

### Step 4: Verify common-portal is Working

```bash
curl -I https://common-portal.mondax.co
```

---

## Switching FROM common-portal TO xramp.io

See: `XRAMP-SERVER-REBOOT-README.md` in this same directory.

Quick summary:
```bash
# Stop common-portal
cd /root/CascadeProjects/common-portal
docker compose down

# Start xramp.io
cd /root/CascadeProjects/xramp.io
docker compose up -d

# Ensure nginx has xramp.io config
sudo ln -sf /etc/nginx/sites-available/xramp.io /etc/nginx/sites-enabled/xramp.io
sudo systemctl reload nginx
```

---

## Running BOTH Sites Simultaneously

**This is possible** if you configure different Docker ports, but requires changes:

### Option A: Different External Ports (Recommended)

Edit one project's `docker-compose.yml` to use different ports:

```yaml
# In common-portal/docker-compose.yml
services:
  app:
    ports:
      - "9080:80"  # Instead of 8080:80
  postgres:
    ports:
      - "5433:5432"  # Instead of 5432:5432
```

Then both can run, and nginx proxies:
- xramp.io → localhost:8080
- common-portal.mondax.co → localhost:9080

### Option B: Separate Docker Networks

Use different network names and container names to avoid conflicts.

---

## Common-Portal Recovery After Reboot

If you rebooted and want common-portal (not xramp.io):

```bash
# 1. Make sure xramp.io is stopped
cd /root/CascadeProjects/xramp.io
docker compose down

# 2. Start common-portal
cd /root/CascadeProjects/common-portal
docker compose up -d

# 3. Verify database
docker exec <common-portal-postgres-container> psql -U platform -d platform -c "\dt"

# 4. Reload nginx
sudo systemctl reload nginx

# 5. Test
curl -I https://common-portal.mondax.co
```

---

## Key Differences Between Projects

| Setting | xramp.io | common-portal |
|---------|----------|---------------|
| Project Path | `/root/CascadeProjects/xramp.io/` | `/root/CascadeProjects/common-portal/` |
| Domain | xramp.io | common-portal.mondax.co |
| DB Name | xramp | platform |
| DB User | xramp | platform |
| DB Volume | xrampio_xramp_pgdata | (see docker-compose.yml) |
| SSL Certs | certbot/conf/live/xramp.io/ | /etc/letsencrypt/live/common-portal.mondax.co/ |

---

## Database Configuration Details

### xramp.io Database

| Setting | Value |
|---------|-------|
| Host | `postgres` (Docker network name) |
| Port | 5432 |
| Database | `xramp` |
| Username | `xramp` |
| Password | `secret` |
| Docker Volume | `xrampio_xramp_pgdata` |
| Container Name | `platform-postgres` |

**docker-compose.yml configuration:**
```yaml
postgres:
  image: postgres:16-alpine
  container_name: platform-postgres
  environment:
    POSTGRES_DB: xramp
    POSTGRES_USER: xramp
    POSTGRES_PASSWORD: secret
  volumes:
    - xramp_pgdata:/var/lib/postgresql/data

volumes:
  xramp_pgdata:
    external: true
    name: xrampio_xramp_pgdata
```

**Verify database connection:**
```bash
docker exec platform-postgres psql -U xramp -d xramp -c "\dt"
```

### common-portal Database

| Setting | Value |
|---------|-------|
| Host | `postgres` (Docker network name) |
| Port | 5432 |
| Database | `platform` |
| Username | `platform` |
| Password | `secret` (check .env) |
| Docker Volume | Check docker-compose.yml |
| Container Name | Check docker-compose.yml |

**Verify database connection:**
```bash
# Adjust container name based on your docker-compose.yml
docker exec <postgres-container> psql -U platform -d platform -c "\dt"
```

### .env File Database Settings

Both projects use `.env` files in their `src/` directories:

**xramp.io** (`/root/CascadeProjects/xramp.io/src/.env`):
```
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=xramp
DB_USERNAME=xramp
DB_PASSWORD=secret
```

**common-portal** (`/root/CascadeProjects/common-portal/src/.env`):
```
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=platform
DB_USERNAME=platform
DB_PASSWORD=secret
```

---

## Troubleshooting

### Port Already in Use

```bash
# Find what's using port 8080
sudo ss -tlnp | grep 8080

# Kill conflicting containers
docker ps -a  # Find container names
docker stop <container_name>
docker rm <container_name>
```

### Database Connection Issues

Each project has its own database. Make sure:
1. The correct Docker containers are running
2. The `.env` file points to the correct database
3. The Docker volume with data is mounted

### Nginx Configuration

Both sites can coexist in nginx since they're different domains:
- `/etc/nginx/sites-enabled/xramp.io`
- `/etc/nginx/sites-enabled/common-portal.mondax.co`

The key is that only ONE Docker container can be on port 8080, so nginx proxies to whichever one is running.

---

## Quick Reference Commands

```bash
# Check which Docker containers are running
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Check which ports are in use
sudo ss -tlnp | grep -E "8080|5432|6379"

# Check nginx sites enabled
ls -la /etc/nginx/sites-enabled/

# View nginx error log
sudo tail -f /var/log/nginx/error.log
```

---

*Last Updated: February 1, 2026*
*Related: See XRAMP-SERVER-REBOOT-README.md for xramp.io specific recovery*
