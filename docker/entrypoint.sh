#!/bin/bash
set -e

APP_DIR=/var/www/html
OVERLAY=/opt/youtube-studio-overlay

if [ ! -f "$APP_DIR/artisan" ]; then
    echo "==> Creating a fresh Laravel 13 application..."
    rm -rf /tmp/laravel
    composer create-project laravel/laravel:^13.0 /tmp/laravel --no-interaction
    cp -a /tmp/laravel/. "$APP_DIR/"
    cp "$OVERLAY/.env.docker.example" "$APP_DIR/.env.docker.example"
    rm -rf /tmp/laravel

    echo "==> Applying YouTube Studio application code..."
    cp -a "$OVERLAY/app/." "$APP_DIR/app/"
    cp -a "$OVERLAY/config/youtube_studio.php" "$APP_DIR/config/youtube_studio.php"
    cp -a "$OVERLAY/database/." "$APP_DIR/database/"
    cp -a "$OVERLAY/resources/." "$APP_DIR/resources/"
    cp -a "$OVERLAY/routes/." "$APP_DIR/routes/"
    cp -a "$OVERLAY/vite.config.js" "$APP_DIR/vite.config.js"
    cp -a "$OVERLAY/tests/." "$APP_DIR/tests/"

    # Keep only the application's migrations. Laravel's default migrations are not needed here.
    find "$APP_DIR/database/migrations" -type f -delete
    cp -a "$OVERLAY/database/migrations/." "$APP_DIR/database/migrations/"

    echo "==> Laravel base created successfully."
else
    echo "==> Existing Laravel installation detected. Skipping initialization."
fi

if [ ! -f "$APP_DIR/.env" ]; then
    if [ -f "$APP_DIR/.env.docker.example" ]; then
        cp "$APP_DIR/.env.docker.example" "$APP_DIR/.env"
    elif [ -f "$APP_DIR/.env.example" ]; then
        cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    fi
fi

if ! grep -q '^APP_KEY=base64:' "$APP_DIR/.env" 2>/dev/null; then
    php "$APP_DIR/artisan" key:generate --force
fi

mkdir -p "$APP_DIR/storage/app/projects" \
         "$APP_DIR/storage/framework/cache" \
         "$APP_DIR/storage/framework/sessions" \
         "$APP_DIR/storage/framework/views" \
         "$APP_DIR/storage/logs"

chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

exec "$@"
