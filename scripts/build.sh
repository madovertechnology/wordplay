#!/bin/bash

# Production build script for Laravel application
set -e

echo "🚀 Starting production build..."

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --classmap-authoritative --optimize-autoloader

# Cache Laravel configurations
echo "⚙️  Caching Laravel configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Install and build frontend assets
echo "🎨 Building frontend assets..."
npm ci
npm run build:prod

# Clean up node_modules to save space
echo "🧹 Cleaning up node_modules..."
rm -rf node_modules

echo "✅ Production build completed successfully!"
