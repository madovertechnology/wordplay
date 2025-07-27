#!/bin/bash

# Laravel Cloud Deployment Script
# This script handles deployment tasks for different environments

set -e

ENVIRONMENT=${1:-staging}
BRANCH=${2:-main}

echo "🚀 Starting deployment to $ENVIRONMENT environment from $BRANCH branch"

# Validate environment
if [[ ! "$ENVIRONMENT" =~ ^(development|staging|production)$ ]]; then
    echo "❌ Invalid environment: $ENVIRONMENT"
    echo "Valid environments: development, staging, production"
    exit 1
fi

# Pre-deployment checks
echo "🔍 Running pre-deployment checks..."

# Check if required environment variables are set
if [ "$ENVIRONMENT" = "production" ]; then
    required_vars=("APP_KEY" "DB_PASSWORD" "GOOGLE_CLIENT_ID" "GOOGLE_CLIENT_SECRET")
    for var in "${required_vars[@]}"; do
        if [ -z "${!var}" ]; then
            echo "❌ Required environment variable $var is not set"
            exit 1
        fi
    done
fi

# Run tests before deployment
echo "🧪 Running tests..."
php artisan test --stop-on-failure

# Check code quality
echo "🔍 Checking code quality..."
if command -v ./vendor/bin/php-cs-fixer &> /dev/null; then
    ./vendor/bin/php-cs-fixer fix --dry-run --diff
fi

if command -v ./vendor/bin/phpstan &> /dev/null; then
    ./vendor/bin/phpstan analyse --memory-limit=2G
fi

# Build assets
echo "🏗️ Building frontend assets..."
npm ci
npm run build

# Clear and optimize caches
echo "🧹 Optimizing application..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

if [ "$ENVIRONMENT" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

# Run migrations
echo "📊 Running database migrations..."
php artisan migrate --force

# Seed essential data
echo "🌱 Seeding database..."
php artisan db:seed --class=GameSeeder --force

# Restart queue workers
echo "🔄 Restarting queue workers..."
php artisan queue:restart

# Generate daily puzzle if needed
echo "🎮 Ensuring daily puzzle exists..."
php artisan game:generate-word-scramble-puzzle --days=1

# Warm up caches
echo "🔥 Warming up caches..."
php artisan cache:warm 2>/dev/null || echo "Cache warming not available"

# Health check
echo "🏥 Running health check..."
sleep 5  # Give the application a moment to start
if curl -f -s "http://localhost/health" > /dev/null; then
    echo "✅ Health check passed"
else
    echo "❌ Health check failed"
    exit 1
fi

# Post-deployment verification
echo "🔍 Running post-deployment verification..."

# Check database connectivity
php artisan db:health-check || {
    echo "❌ Database health check failed"
    exit 1
}

# Check that essential routes are working
routes_to_check=(
    "/health"
    "/api/games/word-scramble/puzzle"
)

for route in "${routes_to_check[@]}"; do
    if curl -f -s "http://localhost$route" > /dev/null; then
        echo "✅ Route $route is working"
    else
        echo "❌ Route $route is not working"
        exit 1
    fi
done

# Create deployment log
echo "📝 Creating deployment log..."
cat > storage/logs/deployment.log << EOF
Deployment completed successfully
Environment: $ENVIRONMENT
Branch: $BRANCH
Timestamp: $(date -u +"%Y-%m-%d %H:%M:%S UTC")
Git Commit: $(git rev-parse HEAD)
Git Branch: $(git branch --show-current)
PHP Version: $(php -v | head -n 1)
Laravel Version: $(php artisan --version)
EOF

echo "🎉 Deployment to $ENVIRONMENT completed successfully!"

# Send notification (placeholder)
if [ "$ENVIRONMENT" = "production" ]; then
    echo "📢 Sending deployment notification..."
    # Add your notification logic here (Slack, email, etc.)
fi

exit 0