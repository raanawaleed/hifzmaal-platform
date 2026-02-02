#!/bin/bash

echo "🚀 Setting up HifzMaal..."

# Copy environment file
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✅ Environment file created"
fi

# Install composer dependencies
echo "📦 Installing composer dependencies..."
composer install

# Generate application key
php artisan key:generate
echo "🔑 Application key generated"

# Create database
echo "🗄️  Creating database..."
php artisan db:create 2>/dev/null || echo "Database already exists or manual creation needed"

# Run migrations
echo "🔄 Running migrations..."
php artisan migrate --seed

# Create storage link
php artisan storage:link
echo "🔗 Storage link created"

# Install npm dependencies
echo "📦 Installing npm dependencies..."
npm install

# Build assets
echo "🎨 Building assets..."
npm run build

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "✨ Setup complete!"
echo ""
echo "Next steps:"
echo "1. Configure your .env file with database credentials"
echo "2. Run: php artisan migrate --seed"
echo "3. Run: php artisan serve"
echo "4. Visit: http://localhost:8000"