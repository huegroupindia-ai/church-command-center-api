#!/bin/sh
set -e

# Parse DATABASE_URL if set (Railway, Heroku, etc.)
if [ -n "$DATABASE_URL" ]; then
    # Extract components from postgres://user:pass@host:port/dbname
    DB_USER=$(echo "$DATABASE_URL" | sed -n 's|.*://\([^:]*\):.*|\1|p')
    DB_PASS=$(echo "$DATABASE_URL" | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')
    DB_HOST=$(echo "$DATABASE_URL" | sed -n 's|.*@\([^:]*\):.*|\1|p')
    DB_PORT=$(echo "$DATABASE_URL" | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
    DB_NAME=$(echo "$DATABASE_URL" | sed -n 's|.*/\([^?]*\).*|\1|p')
    
    # Determine driver from scheme
    SCHEME=$(echo "$DATABASE_URL" | sed -n 's|://\([^:]*\):.*|\1|p')
    case "$SCHEME" in
        postgres|postgresql) DB_DRIVER="pgsql" ;;
        mysql|mariadb) DB_DRIVER="mysql" ;;
        sqlite) DB_DRIVER="sqlite" ;;
        *) DB_DRIVER="pgsql" ;;
    esac
    
    export DB_CONNECTION="${DB_CONNECTION:-$DB_DRIVER}"
    export DB_HOST="${DB_HOST:-$DB_HOST}"
    export DB_PORT="${DB_PORT:-$DB_PORT}"
    export DB_DATABASE="${DB_DATABASE:-$DB_NAME}"
    export DB_USERNAME="${DB_USERNAME:-$DB_USER}"
    export DB_PASSWORD="${DB_PASSWORD:-$DB_PASS}"
fi

# Ensure storage directories exist
mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear config cache so env changes take effect
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true

# Run migrations
php artisan migrate --force 2>/dev/null || true

# Start the server
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
