#!/bin/sh
set -e

echo "=== Church Command Center Backend Starting ==="

# Parse DATABASE_URL if set (Railway, Heroku, etc.)
if [ -n "$DATABASE_URL" ]; then
    echo "DATABASE_URL found: ${DATABASE_URL:0:30}..."
    
    # Extract components from postgres://user:pass@host:port/dbname
    DB_USER=$(echo "$DATABASE_URL" | sed 's|^[^:]*://\([^:]*\):.*|\1|')
    DB_PASS=$(echo "$DATABASE_URL" | sed 's|^[^:]*://[^:]*:\([^@]*\)@.*|\1|')
    DB_HOST=$(echo "$DATABASE_URL" | sed 's|.*@\([^:]*\):.*|\1|')
    DB_PORT=$(echo "$DATABASE_URL" | sed 's|.*:\([0-9]*\)/.*|\1|')
    DB_NAME=$(echo "$DATABASE_URL" | sed 's|.*/\([^?]*\).*|\1|')
    DB_SCHEME=$(echo "$DATABASE_URL" | sed 's|\(^[^:]*\)://.*|\1|')
    
    case "$DB_SCHEME" in
        postgres|postgresql) DB_DRIVER="pgsql" ;;
        mysql|mariadb) DB_DRIVER="mysql" ;;
        sqlite) DB_DRIVER="sqlite" ;;
        *) DB_DRIVER="pgsql" ;;
    esac
    
    echo "Parsed: driver=$DB_DRIVER host=$DB_HOST port=$DB_PORT db=$DB_NAME user=$DB_USER"
    
    export DB_CONNECTION="${DB_CONNECTION:-$DB_DRIVER}"
    export DB_HOST="$DB_HOST"
    export DB_PORT="$DB_PORT"
    export DB_DATABASE="$DB_NAME"
    export DB_USERNAME="$DB_USER"
    export DB_PASSWORD="$DB_PASS"
else
    echo "No DATABASE_URL found, using SQLite default"
    export DB_CONNECTION="sqlite"
    export DB_DATABASE="database/database.sqlite"
fi

# Ensure storage directories exist
mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create SQLite file if using SQLite
if [ "$DB_CONNECTION" = "sqlite" ]; then
    touch "$DB_DATABASE"
fi

# Clear all caches
echo "Clearing caches..."
php artisan config:clear 2>&1 || true
php artisan route:clear 2>&1 || true
php artisan view:clear 2>&1 || true
php artisan cache:clear 2>&1 || true

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force 2>&1 || true
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force 2>&1
MIGRATE_EXIT=$?
echo "Migration exit code: $MIGRATE_EXIT"

# Seed database if empty (check if users table has data)
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null || echo "0")
echo "User count: $USER_COUNT"
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "No users found. Seeding database..."
    php artisan db:seed --force 2>&1 || true
fi

# Start the server
echo "Starting Laravel server on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
