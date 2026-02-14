.PHONY: setup up down build logs shell migrate fresh seed test tinker queue

# Initial setup (run once)
setup:
	chmod +x scripts/setup.sh
	./scripts/setup.sh

# Start all containers
up:
	docker-compose up -d

# Stop all containers
down:
	docker-compose down

# Rebuild containers
build:
	docker-compose build --no-cache

# View logs
logs:
	docker-compose logs -f

# Shell into app container
shell:
	docker-compose exec app sh

# Run migrations
migrate:
	docker-compose exec app php artisan migrate

# ⚠️  DANGEROUS: Drops ALL tables and recreates. NEVER run on production.
# Blocked by default. Only works in local dev with explicit confirmation.
fresh:
	@echo "⚠️  WARNING: migrate:fresh DROPS ALL TABLES and destroys all data!"
	@echo "This is for LOCAL DEVELOPMENT ONLY."
	@read -p "Type 'DESTROY' to confirm: " confirm && [ "$$confirm" = "DESTROY" ] || (echo "Aborted." && exit 1)
	docker-compose exec app php artisan migrate:fresh --seed

# ⚠️  Seeders are blocked in production via DatabaseSeeder.php safeguard.
seed:
	@echo "⚠️  WARNING: Seeders can overwrite existing data."
	@echo "In production, seeders are automatically blocked."
	docker-compose exec app php artisan db:seed

# Run tests
test:
	docker-compose exec app php artisan test

# Laravel tinker
tinker:
	docker-compose exec app php artisan tinker

# Watch queue
queue:
	docker-compose exec app php artisan queue:work

# Production: build and push to registry
prod-build:
	docker build -t ghcr.io/common-portal/platform:latest .

prod-push:
	docker push ghcr.io/common-portal/platform:latest

# Production: deploy with managed DB
prod-up:
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

prod-down:
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml down
