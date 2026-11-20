#!/bin/sh
# Prepara Laravel antes de ejecutar el comando del contenedor (api, worker o scheduler).
set -e

if [ "${LARAVEL_OPTIMIZE:-false}" = "true" ]; then
    # Imagen prod: cachea config/rutas/eventos con las variables del contenedor.
    php artisan optimize --no-interaction
else
    # Dev (código montado): nunca dejar cachés que oculten cambios del .env.
    # Solo el contenedor principal limpia: el volumen es compartido y borrar
    # bootstrap/cache mientras worker/scheduler arrancan los tumba.
    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        [ -f vendor/autoload.php ] || composer install --no-interaction --prefer-dist
        php artisan optimize:clear --no-interaction > /dev/null
    fi
fi

# Tablas propias de Laravel (cache, jobs, failed_jobs). Solo en UN contenedor
# (el de la API o un job de release), nunca en worker/scheduler a la vez.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
