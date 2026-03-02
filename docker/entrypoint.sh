#!/bin/sh
set -e

APP_DIR="/var/www/html"

echo "🚀 Agilify – Starting container..."

# ── 1. Ensure .env exists ──────────────────────────────────────────────────
if [ ! -f "$APP_DIR/.env" ]; then
    echo "⚠  No .env found – copying .env.example"
    cp "$APP_DIR/.env.example" "$APP_DIR/.env"
fi

# ── 2. Generate app key if missing ────────────────────────────────────────
if ! grep -q "^APP_KEY=base64:" "$APP_DIR/.env" 2>/dev/null; then
    echo "🔑 Generating APP_KEY..."
    php "$APP_DIR/artisan" key:generate --force
fi

# ── 3. Storage symlink ────────────────────────────────────────────────────
php "$APP_DIR/artisan" storage:link --force 2>/dev/null || true

# ── 4. Cache config/routes/views for production ───────────────────────────
php "$APP_DIR/artisan" config:cache
php "$APP_DIR/artisan" route:cache
php "$APP_DIR/artisan" view:clear
php "$APP_DIR/artisan" view:cache || echo "⚠  view:cache skipped (views will compile on first request)"
php "$APP_DIR/artisan" event:cache

# ── 5. Run migrations ─────────────────────────────────────────────────────
echo "🗄  Running migrations..."
php "$APP_DIR/artisan" migrate --force

# ── 6. Ensure log directory is writable ───────────────────────────────────
mkdir -p "$APP_DIR/storage/logs"
touch "$APP_DIR/storage/logs/laravel.log"
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# ── 7. Ensure supervisor log dir exists ───────────────────────────────────
mkdir -p /var/log/supervisor

echo "✅ Boot complete – launching Supervisor"

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
