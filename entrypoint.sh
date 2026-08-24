#!/bin/sh
set -e

# ── Auto-parse Railway PostgreSQL DATABASE_URL ──
# Railway provides DATABASE_URL or individual PG* vars
if [ -n "$DATABASE_URL" ]; then
  # Parse postgres://USER:PASS@HOST:PORT/DBNAME
  DB_USER=$(echo "$DATABASE_URL" | sed -n 's|.*://\([^:]*\):.*|\1|p')
  DB_PASS=$(echo "$DATABASE_URL" | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')
  DB_HOST=$(echo "$DATABASE_URL" | sed -n 's|.*@\([^:]*\):.*|\1|p')
  DB_PORT=$(echo "$DATABASE_URL" | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
  DB_NAME=$(echo "$DATABASE_URL" | sed -n 's|.*/\([^?]*\).*|\1|p')

  export DB_HOST="${DB_HOST:-$PGHOST}"
  export DB_PORT="${DB_PORT:-$PGPORT}"
  export DB_DATABASE="${DB_NAME:-$PGDATABASE}"
  export DB_USERNAME="${DB_USER:-$PGUSER}"
  export DB_PASSWORD="${DB_PASS:-$PGPASSWORD}"
fi

# Fallback: use Railway's auto-linked PG* variables
if [ -z "$DB_HOST" ] && [ -n "$PGHOST" ]; then
  export DB_HOST="$PGHOST"
  export DB_PORT="${PGPORT:-5432}"
  export DB_DATABASE="$PGDATABASE"
  export DB_USERNAME="$PGUSER"
  export DB_PASSWORD="$PGPASSWORD"
fi

export DB_CONNECTION="${DB_CONNECTION:-pgsql}"

echo "=== Database Config ==="
echo "DB_CONNECTION=$DB_CONNECTION"
echo "DB_HOST=$DB_HOST"
echo "DB_PORT=$DB_PORT"
echo "DB_DATABASE=$DB_DATABASE"
echo "DB_USERNAME=$DB_USERNAME"
echo "========================"

# Run migrations and seed
php artisan migrate --force

# Seed database if users table is empty
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ]; then
  echo "Seeding database..."
  php artisan db:seed --force
fi

# Cache config
php artisan config:cache
php artisan route:cache

# Start the server
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
