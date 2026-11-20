# syntax=docker/dockerfile:1.7
#
# Imagen única para api / worker / scheduler (cambia solo el comando).
#   docker build --target prod -t gestopago-api .
#   docker build --target dev  -t gestopago-api:dev .

ARG PHP_VERSION=8.4

# --- base: runtime FrankenPHP (Caddy + PHP) con las extensiones necesarias -----
FROM dunglas/frankenphp:1-php${PHP_VERSION}-alpine AS base

RUN install-php-extensions pdo_pgsql redis opcache intl pcntl bcmath zip \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/app.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint

ENV SERVER_NAME=:8080 \
    APP_PORT=8080 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app
EXPOSE 8080

# --- dev: código montado como volumen, con pcov para cobertura -----------------
FROM base AS dev

RUN install-php-extensions pcov \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# --- vendor: dependencias de producción (capa cacheable) ------------------------
FROM base AS vendor

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# --- prod: imagen final, sin herramientas de desarrollo, usuario sin privilegios -
FROM base AS prod

ARG APP_VERSION=dev
ENV APP_ENV=production \
    LARAVEL_OPTIMIZE=true \
    APP_DEBUG=false \
    APP_VERSION=${APP_VERSION} \
    LOG_CHANNEL=stderr

COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN echo "opcache.validate_timestamps=0" > "$PHP_INI_DIR/conf.d/zz-prod.ini" \
    && composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction \
    && rm -f /usr/bin/composer \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && adduser -D -H -u 10001 -s /sbin/nologin app \
    && chown -R app:app storage bootstrap/cache /config/caddy /data/caddy

USER app

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1:8080/up") === false ? 1 : 0);'

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
