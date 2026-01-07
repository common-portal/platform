#!/bin/bash
set -e

echo "🚀 Setting up Common Portal Platform..."

# Check if src directory has Laravel installed
if [ ! -f "src/artisan" ]; then
    echo "📦 Installing Laravel..."
    
    # Create Laravel project in src directory
    composer create-project laravel/laravel src --prefer-dist
    
    cd src
    
    echo "📦 Installing Jetstream with Livewire (Teams enabled)..."
    composer require laravel/jetstream
    php artisan jetstream:install livewire --teams
    
    echo "📦 Installing additional packages..."
    # Spatie permissions for role management
    composer require spatie/laravel-permission
    
    # Tenancy for multi-tenant subdomains
    composer require stancl/tenancy
    
    echo "📦 Installing Tailwind and building assets..."
    npm install
    npm run build
    
    echo "📝 Publishing package configs..."
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
    php artisan vendor:publish --provider="Stancl\Tenancy\TenancyServiceProvider"
    
    cd ..
else
    echo "✅ Laravel already installed in src/"
fi

# Copy environment file if not exists
if [ ! -f ".env" ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
fi

# Copy to src if not exists
if [ ! -f "src/.env" ]; then
    cp .env.example src/.env
fi

echo "🔑 Generating application key..."
cd src && php artisan key:generate && cd ..

echo ""
echo "✅ Setup complete!"
echo ""
echo "Next steps:"
echo "  1. Update .env with your database credentials"
echo "  2. Run: docker-compose up -d"
echo "  3. Run: docker-compose exec app php artisan migrate"
echo "  4. Visit: http://localhost:8080"
echo ""
