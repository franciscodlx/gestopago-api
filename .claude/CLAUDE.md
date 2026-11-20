# Gesto Pago API — instrucciones del proyecto

Sucesor de `wsgestopago` (Lumen 8): API Laravel 13 / PHP 8.4 que integra el agregador
de pagos de servicios **Gesto Pago** para Helm MX Farma. El `CLAUDE.md` de la raíz lo
genera Laravel Boost (`php artisan boost:update`); lo propio del proyecto va aquí y en
`.claude/rules/`.

## Mapa

- `app/Domain/GestoPago` — puertos (`Contracts`), DTOs (`Data`), `Upc`, excepciones con estado HTTP.
- `app/Application/GestoPago` — casos de uso (`SendPayment`, `ConfirmPayment`, ...). Sin HTTP ni SQL.
- `app/Infrastructure/GestoPago` — cliente HTTP, AES, parser XML, token store.
- `app/Infrastructure/Persistence` — funciones `gestopago.*` de PostgreSQL vía `StoredFunctions`.
- `app/Http/Controllers/Api/V1` — API nueva. `app/Http/Controllers/Legacy` — contrato de wsgestopago.
- `app/Providers/GestoPagoServiceProvider.php` — enlaza puertos ↔ adaptadores.
- `database/sql/gestopago_schema.sql` — esquema de referencia (local/CI), reconstruido.
- `bruno/` — colección de API. `docs/openapi.json` — export de Scramble.

## Comandos

```bash
docker compose up -d --build                         # api :8080, worker, scheduler, postgres, redis
vendor/bin/pest                                      # unit + feature (sin BD)
docker compose exec -e DB_CONNECTION=pgsql -e DB_DATABASE=gestopago_test app \
  vendor/bin/pest --group=default --group=pgsql --coverage   # suite completa
vendor/bin/pint && vendor/bin/phpstan analyse --memory-limit=1G
php artisan scramble:export --path=docs/openapi.json # tras cambiar endpoints
```

## Reglas que no se negocian

- Nunca leer, imprimir ni commitear `.env`; las credenciales de Gesto Pago solo viven ahí.
- `sendTransaction` NO se reintenta (no es idempotente). La transacción se persiste antes de enviarla.
- Las rutas `/api/{PRODUCTION|DEVELOP}/*` conservan el contrato heredado byte a byte (ver `docs/MIGRATION.md`).
- Nada de SQL interpolado: todo pasa por `StoredFunctions` con bindings.
- Cambios de endpoints → actualizar tests, `docs/openapi.json` y `bruno/`.
