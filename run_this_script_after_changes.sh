#!/bin/bash
# Quick refresh script for xramp.io after code changes
# Usage: /root/CascadeProjects/xramp.io/run_this_script_after_changes.sh

cd /root/CascadeProjects/xramp.io

echo "🔄 Clearing Laravel view cache..."
docker compose -f docker-compose.ssl.yml exec app php artisan view:clear

echo "🔄 Clearing Laravel cache..."
docker compose -f docker-compose.ssl.yml exec app php artisan cache:clear

echo "🔄 Restarting app container..."
docker compose -f docker-compose.ssl.yml restart app

echo "✅ Done! Hard refresh your browser (Ctrl+Shift+R)"
