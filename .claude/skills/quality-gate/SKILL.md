---
name: quality-gate
description: Corre las mismas verificaciones que CI (Pint, Larastan nivel 8, Pest con cobertura contra PostgreSQL) y resume el resultado. Úsalo antes de dar por terminado un cambio o abrir un PR.
allowed-tools: Bash(vendor/bin/pint:*), Bash(vendor/bin/phpstan:*), Bash(vendor/bin/pest:*), Bash(docker compose:*), Bash(php artisan scramble:export:*)
---

# Quality gate

Ejecuta en orden y detente en el primer fallo, mostrando el error relevante:

1. `vendor/bin/pint --format agent`
2. `vendor/bin/phpstan analyse --memory-limit=1G --no-progress`
3. `vendor/bin/pest --compact`
4. Si `docker compose ps` muestra `postgres` arriba:
   `docker compose exec -T -e DB_CONNECTION=pgsql -e DB_DATABASE=gestopago_test app vendor/bin/pest --group=default --group=pgsql --coverage --min=90`
5. Si cambiaron rutas o FormRequests: `php artisan scramble:export --path=docs/openapi.json` y revisa el diff.

Resume en una tabla: paso, resultado, detalle. No ocultes fallos.
