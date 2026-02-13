#!/bin/bash
# Quick refresh script for directdebit.now after code changes
# Usage: /root/CascadeProjects/directdebit.now/run_this_script_after_changes-directdebit.sh

cd /root/CascadeProjects/directdebit.now

echo "🔄 Clearing Laravel view cache..."
docker exec directdebit-app php artisan view:clear

echo "🔄 Clearing Laravel cache..."
docker exec directdebit-app php artisan cache:clear

echo "🔄 Clearing config cache..."
docker exec directdebit-app php artisan config:clear

echo "🔄 Restarting app container..."
docker restart directdebit-app

echo "✅ Done! Hard refresh your browser (Ctrl+Shift+R)"
echo "📍 DirectDebit.now should be available at http://localhost:8081"
