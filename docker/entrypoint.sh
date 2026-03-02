#!/bin/sh
set -e

APP_DIR="/var/www/html"

echo "==> Agilify: starting container..."

# ── 1. Ensure .env exists ──────────────────────────────────────────────────
if [ ! -f "$APP_DIR/.env" ]; then
    echo "==> No .env found – copying .env.example"
    cp "$APP_DIR/.env.example" "$APP_DIR/.env"
fi

# ── 2. Generate app key if missing ────────────────────────────────────────
if ! grep -q "^APP_KEY=base64:" "$APP_DIR/.env" 2>/dev/null; then
    echo "==> Generating APP_KEY..."
    php "$APP_DIR/artisan" key:generate --force
fi

# ── 3. Ensure all required directories exist and are writable ─────────────
echo "==> Preparing storage directories..."
mkdir -p \
    "$APP_DIR/bootstrap/cache" \
    "$APP_DIR/storage/framework/sessions" \
    "$APP_DIR/storage/framework/views" \
    "$APP_DIR/storage/framework/cache/data" \
    "$APP_DIR/storage/logs" \
    /var/log/supervisor

touch "$APP_DIR/storage/logs/laravel.log"
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# ── 4. Storage symlink ────────────────────────────────────────────────────
php "$APP_DIR/artisan" storage:link --force 2>/dev/null || true

# ── 5. Cache config / routes / views / events for production ──────────────
echo "==> Caching Laravel application..."
php "$APP_DIR/artisan" config:cache
php "$APP_DIR/artisan" route:cache
php "$APP_DIR/artisan" view:clear  || true
php "$APP_DIR/artisan" view:cache  || true
php "$APP_DIR/artisan" event:cache

# ── 6. Run migrations ─────────────────────────────────────────────────────
echo "==> Running migrations..."
php "$APP_DIR/artisan" migrate --force

echo "==> Boot complete – launching Supervisor"
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
