#!/bin/sh
set -e

# Parse DATABASE_URL if set (Railway, Heroku, etc.)
if [ -n "$DATABASE_URL" ]; then
    # Extract components from postgres://user:pass@host:port/dbname
    DB_USER=$(echo "$DATABASE_URL" | sed 's|^[^:]*://\([^:]*\):.*|\1|')
    DB_PASS=$(echo "$DATABASE_URL" | sed 's|^[^:]*://[^:]*:\([^@]*\)@.*|\1|')
    DB_HOST=$(echo "$DATABASE_URL" | sed 's|.*@\([^:]*\):.*|\1|')
    DB_PORT=$(echo "$DATABASE_URL" | sed 's|.*:\([0-9]*\)/.*|\1|')
    DB_NAME=$(echo "$DATABASE_URL" | sed 's|.*/\([^?]*\).*|\1|')
    
    # Determine driver from scheme
    DB_SCHEME=$(echo "$DATABASE_URL" | sed 's|\(^[^:]*\)://.*|\1|')
    case "$DB_SCHEME" in
        postgres|postgresql) DB_DRIVER="pgsql" ;;
        mysql|mariadb) DB_DRIVER="mysql" ;;
        sqlite) DB_DRIVER="sqlite" ;;
        *) DB_DRIVER="pgsql" ;;
    esac
    
    echo "=== Database Configuration ==="
    echo "Driver: $DB_DRIVER"
    echo "Host: $DB_HOST"
    echo "Port: $DB_PORT"
    echo "Database: $DB_NAME"
    echo "Username: $DB_USER"
    echo "=============================="
    
    export DB_CONNECTION="${DB_CONNECTION:-$DB_DRIVER}"
    export DB_HOST="${DB_HOST}"
    export DB_PORT="${DB_PORT}"
    export DB_DATABASE="${DB_NAME}"
    export DB_USERNAME="${DB_USER}"
    export DB_PASSWORD="${DB_PASS}"
else
    echo "No DATABASE_URL found, using defaults"
fi

# Ensure storage directories exist
mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear config cache so env changes take effect
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Run migrations
php artisan migrate --force 2>/dev/null || true

# Seed database if empty
php artisan db:seed --force 2>/dev/null || true

# Start the server
echo "Starting Laravel server on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
