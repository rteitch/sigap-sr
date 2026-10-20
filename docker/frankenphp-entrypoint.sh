#!/bin/sh
set -e

echo "[SIGAP-SR FrankenPHP] Initializing SIGAP-SR application..."

# 0. Clean any stale bootstrap cache
rm -f /var/www/html/bootstrap/cache/config.php \
      /var/www/html/bootstrap/cache/routes-v7.php \
      /var/www/html/bootstrap/cache/packages.php \
      /var/www/html/bootstrap/cache/services.php
php /var/www/html/artisan package:discover --ansi || true

# 1. Environment file check
if [ ! -f /var/www/html/.env ]; then
    echo "[SIGAP-SR FrankenPHP] .env not found. Creating from .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# 2. Application Key check
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
    echo "[SIGAP-SR FrankenPHP] Generating application encryption key..."
    php /var/www/html/artisan key:generate --force --no-interaction
fi

# 3. SQLite Database initialization
DB_CONN=$(grep -E "^DB_CONNECTION=" /var/www/html/.env | cut -d '=' -f2 | tr -d ' "' || echo "sqlite")
if [ "$DB_CONN" = "sqlite" ] || [ -z "$DB_CONN" ]; then
    mkdir -p /var/www/html/database
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        echo "[SIGAP-SR FrankenPHP] Creating fresh database/database.sqlite..."
        touch /var/www/html/database/database.sqlite
    fi
    chown -R www-data:www-data /var/www/html/database
    chmod 775 /var/www/html/database
    chmod 664 /var/www/html/database/database.sqlite 2>/dev/null || true
fi

# 4. Storage permissions & symlink
echo "[SIGAP-SR FrankenPHP] Verifying storage permissions and symlinks..."
mkdir -p /var/www/html/storage/app/public \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

php /var/www/html/artisan storage:link --force --no-interaction || true

# 5. Database migrations
echo "[SIGAP-SR FrankenPHP] Running database migrations..."
php /var/www/html/artisan migrate --force --no-interaction

# 6. Database seeder (if RUN_SEEDER=true is supplied)
if [ "${RUN_SEEDER:-false}" = "true" ]; then
    echo "[SIGAP-SR FrankenPHP] RUN_SEEDER is true. Running database seeders..."
    php /var/www/html/artisan db:seed --force --no-interaction || true
fi

# 7. Production Caches Optimization
echo "[SIGAP-SR FrankenPHP] Optimizing Laravel caches (config, route, view, event)..."
php /var/www/html/artisan config:cache --no-interaction
php /var/www/html/artisan route:cache --no-interaction
php /var/www/html/artisan view:cache --no-interaction
php /var/www/html/artisan event:cache --no-interaction

echo "[SIGAP-SR FrankenPHP] Ready! Launching FrankenPHP HTTP/3 Server..."
exec "$@"
