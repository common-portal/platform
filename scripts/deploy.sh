#!/bin/bash
set -e

echo "🚀 Deploying Common Portal Platform..."

# Pull latest code
git pull origin main

# Pull latest image from registry
docker pull ghcr.io/common-portal/platform:latest

# Run database migrations
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force

# Clear caches
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:cache

# Restart containers with new image
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --force-recreate

echo "✅ Deployment complete!"
