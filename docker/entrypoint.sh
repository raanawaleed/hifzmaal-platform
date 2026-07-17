#!/bin/sh
set -e

cd /var/www/html

# Belt-and-suspenders alongside docker-compose's `depends_on` healthcheck —
# this also covers `docker run` usage without compose, and any orchestrator
# that doesn't wait on healthchecks itself.
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    until nc -z -w1 "$DB_HOST" "${DB_PORT:-3306}" 2>/dev/null; do
        sleep 1
    done
    echo "Database is reachable."
fi

# Re-assert ownership: a mounted volume (e.g. storage/ in local dev, or a
# fresh named volume in compose) can come in owned by someone other than
# www-data, even though the image itself sets this correctly at build time.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Deliberately NOT running migrations here — see the Dockerfile comment.
# Run `docker compose exec app php artisan migrate --force` yourself,
# once, whichever container ends up doing it in a multi-replica setup.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
