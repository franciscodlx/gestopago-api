# Gesto Pago API

API de pagos de servicios, recargas y PINs para **Helm MX Farma** a través del agregador
**Gesto Pago**. Reemplaza a [`wsgestopago`](../wsgestopago) (Lumen 8) manteniendo
compatibilidad con sus clientes actuales.

| | |
|---|---|
| Stack | Laravel 13 · PHP 8.4 · PostgreSQL 17 · Redis 7 · FrankenPHP |
| Arquitectura | Puertos y adaptadores (dominio / casos de uso / infraestructura / HTTP) |
| Calidad | Pest 4 (86 tests, ~95 % de cobertura) · Larastan nivel 8 · Pint |
| Docs de la API | Scramble (OpenAPI 3.1) en `/docs/api`, Swagger UI en `/docs/swagger`, export en `docs/openapi.json` |
| Cliente HTTP | Colección [Bruno](https://www.usebruno.com/) en `bruno/` |
| CI/CD | `franciscodlx/ci-projects` (`laravel-ci@v1`, `secret-scan@v1`) + imagen a GHCR |

## Arranque rápido

```bash
cp .env.example .env            # completa GESTOPAGO_* (URLs, credenciales, llaves AES)
docker compose up -d --build    # api, worker, scheduler, postgres, redis
curl localhost:8080/up
curl localhost:8080/api/v1/categories -H 'X-Api-Key: local-dev-key'
open http://localhost:8080/docs/api
```

Postgres local se inicializa con `database/sql/gestopago_schema.sql` (+ `seed_local.sql`) y crea
también la base `gestopago_test`. Sin Docker: PHP 8.4 con `pdo_pgsql`, `composer install`,
`php artisan serve`.

## Configuración

Todo vive en `config/gestopago.php` y se alimenta de variables de entorno
(ver `.env.example`, que documenta cada una):

| Variable | Qué es |
|---|---|
| `GESTOPAGO_BASE_URL`, `GESTOPAGO_*_PATH` | URL del proveedor y ruta de cada operación (token, echo, validateMe, balance, products, sendTx, confirmTx, verifyReference) |
| `GESTOPAGO_DISTRIBUTOR_ID`, `GESTOPAGO_DEVICE_CODE`, `GESTOPAGO_PASSWORD` | Credenciales del endpoint de token |
| `GESTOPAGO_SECRET_KEY`, `GESTOPAGO_SECRET_IV` | AES-256-CBC en base64 |
| `GESTOPAGO_TOKEN_STORE` | `cache` (Redis, por defecto) o `database` (comparte el JWT con wsgestopago mientras convivan) |
| `GESTOPAGO_LOW_BALANCE_NOTIFY_URL` | Endpoint del core para el aviso de saldo (antes `URL_CORE` + `gp/avisosaldo`) |
| `GESTOPAGO_DIAGNOSTICS_ENABLED` | Habilita `/api/v1/diagnostics/*` (nunca en producción; se bloquea igual) |
| `GESTOPAGO_LEGACY_ROUTES` | Publica las rutas `/api/{PRODUCTION\|DEVELOP}/*` de wsgestopago |
| `API_DOCS_ENABLED` | Sirve `/docs/*` fuera de `APP_ENV=local` |

Las variables cambiaron de nombre respecto a wsgestopago: ver la tabla en
[`docs/MIGRATION.md`](docs/MIGRATION.md#variables-de-entorno).

## API v1

Autenticación: header `X-Api-Key` (se acepta también `key`). Respuestas `{ "data": ... }`.

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/v1/gateway/echo` · `/me` · `/balance` | Eco, datos del distribuidor y saldo con el proveedor |
| POST | `/api/v1/payments` | Enviar pago → `201` con `transactionId`, `authorizationNumber`, `upc`, `pin`, `legend` |
| GET | `/api/v1/payments/{upc}` | Respuesta registrada del proveedor |
| POST | `/api/v1/payments/{upc}/confirm` | Confirmar con el proveedor |
| POST | `/api/v1/references/verify` | Validar referencia antes de cobrar |
| GET | `/api/v1/businesses/{id}/transactions?from=&to=` | Transacciones de un negocio |
| GET | `/api/v1/categories` · `/categories/{id}/products` | Catálogo local |
| POST | `/api/v1/catalog/sync` | Sincronizar catálogo (también `gestopago:sync-products`, diario 04:00) |
| GET/POST | `/api/v1/businesses/{id}/commissions` | Comisiones de un negocio |
| PATCH | `/api/v1/commissions/{id}` | Actualizar comisión |
| POST/GET | `/api/v1/diagnostics/encrypt` · `/token` | Solo sandbox |

Errores: `401` API key, `404` UPC inexistente, `422` validación o rechazo del proveedor
(incluye `upc` y `code`), `429` rate limit, `502` proveedor no disponible.

## Arquitectura

```
app/
├── Domain/GestoPago/          Contracts (puertos), Data (DTOs), ValueObjects/Upc, Exceptions
├── Application/GestoPago/     SendPayment, ConfirmPayment, GetPaymentResponse, VerifyReference, SyncProductCatalog
├── Infrastructure/
│   ├── GestoPago/             HttpGestoPagoGateway, AesPayloadCipher, XmlResponseParser, token store, notifier
│   └── Persistence/           StoredFunctions + repositorios sobre las funciones gestopago.* de PostgreSQL
├── Http/
│   ├── Controllers/Api/V1/    API nueva
│   ├── Controllers/Legacy/    Adaptador del contrato de wsgestopago
│   ├── Middleware/            AuthenticateApiKey, UseLegacyDatabaseConnection, EnsureDiagnosticsEnabled
│   └── Requests/Api/V1/       Validación
├── Jobs/NotifyLowBalance.php  Aviso de saldo en cola (3 intentos con backoff)
└── Providers/GestoPagoServiceProvider.php
```

Decisiones principales:

- **La BD de negocio no cambia**: se siguen usando las funciones `gestopago.*` existentes, ahora
  con parámetros enlazados (`StoredFunctions`) en lugar de SQL interpolado.
- **El pago se registra antes de llamar al proveedor** y `sendTx` nunca se reintenta; el resto de
  operaciones reintenta errores de conexión. Un `401` renueva el JWT y reintenta una vez.
- **Timeouts explícitos** (10 s conexión / 60 s total); wsgestopago no tenía límite.
- **Aviso de saldo en cola**: un fallo del core ya no puede convertir un pago cobrado en error.
- **Bindings `scoped`**: seguro para FrankenPHP worker mode / Octane.

## Tests y calidad

```bash
vendor/bin/pest                                   # unit + feature, sin BD (80 tests)
docker compose exec -T -e DB_CONNECTION=pgsql -e DB_DATABASE=gestopago_test app \
  vendor/bin/pest --group=default --group=pgsql --coverage --min=90
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
```

- `tests/Unit` — cifrado (idéntico al de wsgestopago), parser XML, casos de uso, gateway.
- `tests/Feature` — endpoints v1 y contrato heredado con `Http::fake` y repositorios en memoria.
- `tests/Integration` (grupo `pgsql`) — repositorios contra PostgreSQL, incluida la prueba de inyección SQL.

## Docker

`Dockerfile` multi-stage sobre `dunglas/frankenphp` (Alpine): `dev` (código montado, pcov) y
`prod` (~390 MB, usuario sin privilegios, OPcache sin revalidación, `HEALTHCHECK` en `/up`).
La misma imagen corre API, `queue:work` y `schedule:work`; `RUN_MIGRATIONS=true` solo en uno.

```bash
docker build --target prod -t gestopago-api .
```

## CI/CD

- `.github/workflows/ci.yml` — stub de `franciscodlx/ci-projects`: Pint, Larastan, Pest en PHP 8.4 y
  8.5 contra Postgres 17 con cobertura mínima de 90 %, y gitleaks. Arranca con `allow-failure`
  según la política del hub.
- `.github/workflows/docker.yml` — build de `prod`, push a `ghcr.io` (main y tags `v*`), SBOM,
  provenance y escaneo Trivy.
- `.github/dependabot.yml` — Composer, Actions y Docker semanales.

## Claude Code

Configuración en `.claude/` según la [estructura recomendada](https://code.claude.com/docs/en/claude-directory):
`CLAUDE.md` del proyecto, `rules/` con alcance por rutas, skills (`add-gestopago-operation`,
`quality-gate` y las de Laravel Boost), el subagente `payments-reviewer`, un hook que formatea
con Pint cada PHP editado y permisos que bloquean la lectura de `.env`. El `CLAUDE.md` de la raíz
y `.mcp.json` los gestiona Laravel Boost (`php artisan boost:update`).
