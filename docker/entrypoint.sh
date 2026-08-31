#!/bin/bash
set -e

echo "Starting Church Command Center API..."

# Parse DATABASE_URL if provided (Railway, Render, etc.)
if [ -n "$DATABASE_URL" ]; then
    echo "Parsing DATABASE_URL..."
    
    # Extract components from DATABASE_URL
    # Format: postgresql://username:password@host:port/database
    DB_URL=$(echo $DATABASE_URL | sed -e 's|^postgresql://||' -e 's|^postgres://||')
    DB_USER=$(echo $DB_URL | cut -d: -f1)
    DB_PASS=$(echo $DB_URL | cut -d: -f2 | cut -d@ -f1)
    DB_HOST=$(echo $DB_URL | cut -d@ -f2 | cut -d: -f1)
    DB_PORT=$(echo $DB_URL | cut -d@ -f2 | cut -d: -f2 | cut -d/ -f1)
    DB_NAME=$(echo $DB_URL | cut -d/ -f2)
    
    # Set Laravel database environment variables
    export DB_CONNECTION=pgsql
    export DB_HOST=$DB_HOST
    export DB_PORT=${DB_PORT:-5432}
    export DB_DATABASE=$DB_NAME
    export DB_USERNAME=$DB_USER
    export DB_PASSWORD=$DB_PASS
    
    echo "Database: $DB_HOST:$DB_PORT/$DB_NAME"
fi

# Set APP_URL if not provided
if [ -z "$APP_URL" ]; then
    export APP_URL="http://localhost:8000"
fi

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating app key..."
    php artisan key:generate --force
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Seed database if first run
if [ ! -f /var/www/html/.seeded ]; then
    echo "Seeding database..."
    php artisan db:seed --force
    touch /var/www/html/.seeded
fi

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix storage permissions
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "Church Command Center API ready!"
exec "$@"
