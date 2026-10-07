#!/bin/sh
set -eu

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

php artisan config:clear --no-interaction

if [ "${DB_CONNECTION:-}" = "mysql" ]; then
    echo "Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
    attempt=0

    until php -r '
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                getenv("DB_HOST"),
                getenv("DB_PORT"),
                getenv("DB_DATABASE")
            );
            new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT => 3]);
        } catch (Throwable $exception) {
            exit(1);
        }
    '; do
        attempt=$((attempt + 1))

        if [ "$attempt" -ge 60 ]; then
            echo "MySQL did not become ready in time."
            exit 1
        fi

        sleep 2
    done
fi

php artisan migrate --force --no-interaction

if [ "${SEED_DATABASE:-false}" = "true" ]; then
    php artisan db:seed --force --no-interaction
fi

php artisan storage:link --force --no-interaction >/dev/null 2>&1 || true
php artisan optimize --no-interaction

exec "$@"

