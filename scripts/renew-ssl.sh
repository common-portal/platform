#!/bin/bash

# Renew Let's Encrypt SSL certificates for xramp.io
# This script can be run manually or via cron

set -e

# Change to project directory
cd "$(dirname "$0")/.."

echo "[$(date)] Starting SSL certificate renewal check..."

docker compose -f docker-compose.ssl.yml run --rm certbot renew

if [ $? -eq 0 ]; then
    echo "[$(date)] Reloading nginx to apply any renewed certificates..."
    docker compose -f docker-compose.ssl.yml exec app nginx -s reload
    echo "[$(date)] SSL renewal check complete."
else
    echo "[$(date)] Certificate renewal failed!" >&2
    exit 1
fi
