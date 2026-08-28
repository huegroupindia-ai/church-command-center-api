#!/bin/sh

echo "=== Church Command Center Backend Starting ==="
echo "PHP version: $(php -v | head -1)"
echo "DB_CONNECTION=${DB_CONNECTION:-not set}"
echo "DATABASE_URL set: $([ -n "$DATABASE_URL" ] && echo 'yes' || echo 'no')"

# Parse DATABASE_URL if set
if [ -n "$DATABASE_URL" ]; then
    echo "DATABASE_URL found: ${DATABASE_URL:0:30}..."
    
    DB_USER=$(echo "$DATABASE_URL" | sed 's|^[^:]*://\([^:]*\):.*||')
    DB_PASS=$(echo "$DATABASE_URL" | sed 's|^[^:]*://[^:]*:\([^@]*\)@.*||')
    DB_HOST=$(echo "$DATABASE_URL" | sed 's|.*@\([^:]*\):.*||')
    DB_PORT=$(echo "$DATABASE_URL" | sed 's|.*:\([0-9]*\)/.*||')
    DB_NAME=$(echo "$DATABASE_URL" | sed 's|.*/\([^?]*\).*||')
    DB_SCHEME=$(echo "$DATABASE_URL" | sed 's|\(^[^:]*\)://.*||')
    
    case "$DB_SCHEME" in
        postgres|postgresql) DB_DRIVER="pgsql" ;;
        mysql|mariadb) DB_DRIVER="mysql" ;;
        sqlite) DB_DRIVER="sqlite" ;;
        *) DB_DRIVER="pgsql" ;;
    esac
    
    echo "Parsed: driver=$DB_DRIVER host=$DB_HOST port=$DB_PORT db=$DB_NAME user=$DB_USER"
    
    export DB_CONNECTION="$DB_DRIVER"
    export DB_HOST="$DB_HOST"
    export DB_PORT="$DB_PORT"
    export DB_DATABASE="$DB_NAME"
    export DB_USERNAME="$DB_USER"
    export DB_PASSWORD="$DB_PASS"
else
    echo "No DATABASE_URL found, using SQLite"
    export DB_CONNECTION="sqlite"
    export DB_DATABASE="database/database.sqlite"
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache
chmod -R 775 storage bootstrap/cache

if [ "$DB_CONNECTION" = "sqlite" ]; then
    mkdir -p database
    touch "$DB_DATABASE"
fi

echo "Clearing caches..."
php artisan config:clear 2>&1 || true
php artisan route:clear 2>&1 || true
php artisan view:clear 2>&1 || true
php artisan cache:clear 2>&1 || true

if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force 2>&1 || true
fi

if [ -z "$JWT_SECRET" ]; then
    echo "Generating JWT_SECRET..."
    php artisan jwt:secret --force 2>&1 || true
fi

echo "Running migrations..."
php artisan migrate --force 2>&1 || {
    echo "Migration failed! Trying migrate:fresh..."
    php artisan migrate:fresh --force 2>&1 || true
}

echo "Seeding database..."
php artisan db:seed --force 2>&1 || echo "Seed: already seeded or error (OK)"

echo "=== Ready to serve ==="
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
