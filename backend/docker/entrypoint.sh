#!/usr/bin/env sh
set -e

echo "==> Bootstrapping Laravel in container..."

# If vendor volume is empty, seed it from the baked image vendor
if [ ! -f /var/www/vendor/autoload.php ]; then
  echo "==> vendor/ empty; seeding from /opt/vendor-seed..."
  mkdir -p /var/www/vendor
  cp -r /opt/vendor-seed/* /var/www/vendor/ || true
fi

# Permissions (safe on Linux; no-op on macOS bind mounts)
mkdir -p storage/framework/{cache,data,sessions,views} storage/app/public
chmod -R 777 storage bootstrap/cache || true

# Install if vendor is still incomplete (useful on fresh named volume)
if [ ! -f /var/www/vendor/autoload.php ]; then
  echo "==> Running composer install to populate vendor volume..."
  composer install --no-dev --prefer-dist --no-interaction --no-progress
fi

# Storage symlink (idempotent)
php artisan storage:link || true

# Migrate (idempotent)
php artisan migrate --force || true

echo "==> Ready. Running CMD..."
exec "$@"
