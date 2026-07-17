# Multi-stage build: compile frontend assets and PHP deps in throwaway
# stages, then copy only the finished artifacts into a lean runtime image.
# Nothing here runs migrations or seeds data — that's a deliberate one-off
# step (see INSTALLATION.md's Docker section), not a container-start hook,
# so scaling to multiple app replicas can't race on concurrent migrations.

# ---- Frontend build ----
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources/ resources/
COPY vite.config.js ./
RUN npm run build

# ---- PHP dependencies ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# --no-scripts: artisan (and thus package discovery) isn't runnable yet —
# app/ and .env don't exist in this stage. composer install runs again
# implicitly via the real artisan commands once the full app is copied in
# the runtime stage below.
#
# --ignore-platform-req: this bare composer image has no PHP extensions
# at all, so Composer's platform check rejects moneyphp/money (needs
# ext-bcmath) and spatie/image (needs ext-exif) even though this stage
# only downloads and extracts packages — it never executes their code.
# The runtime stage below actually installs both extensions.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
    --ignore-platform-req=ext-bcmath --ignore-platform-req=ext-exif

# ---- Runtime ----
FROM php:8.3-fpm-alpine AS runtime

RUN apk add --no-cache \
        nginx \
        supervisor \
        mysql-client \
        netcat-openbsd \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        icu-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

# php:8.3-fpm-alpine has no composer binary at all — needed below to
# rebuild the optimized/classmap autoloader against the full app source
# (the vendor stage only ever had composer.json/lock, not app/).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
