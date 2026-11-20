# Migración desde wsgestopago

`gestopago-api` sustituye al servicio Lumen 8 `wsgestopago`. Usa **la misma base de datos**
(funciones del esquema `gestopago`) y publica **las mismas rutas** para que los clientes
actuales no cambien el día del corte.

## Mapa de endpoints

| wsgestopago (`/api/{PRODUCTION\|DEVELOP}/…`) | Compatibilidad | API v1 equivalente |
|---|---|---|
| `POST main` | ✅ igual (`"OK"`) | `GET /up` |
| `POST encryptData` | ✅ solo con diagnóstico habilitado y fuera de producción | `POST /api/v1/diagnostics/encrypt` |
| `POST getJWT` | ✅ ídem | `GET /api/v1/diagnostics/token` |
| `POST sendEcho` | ✅ | `GET /api/v1/gateway/echo` |
| `POST validateMe` | ✅ | `GET /api/v1/gateway/me` |
| `POST getBalance` | ✅ | `GET /api/v1/gateway/balance` |
| `POST getListProducts` | ✅ texto plano | `POST /api/v1/catalog/sync` |
| `POST sendTx` | ✅ `201 {ID_TX, NUM_AUTORIZACION, UPC, PIN?, LEGENT?}` / `500 {MESSAGE, UPC}` | `POST /api/v1/payments` |
| `POST confirmTx` (`upc`) | ✅ | `POST /api/v1/payments/{upc}/confirm` |
| `POST responseTx` (`upc`) | ✅ | `GET /api/v1/payments/{upc}` |
| `POST verifyReference` | ✅ | `POST /api/v1/references/verify` |
| `POST getTransaccionsByClient` (`bussinessid`, `fechaInicio`, `fechaFin`) | ✅ | `GET /api/v1/businesses/{id}/transactions?from&to` |
| `GET categories` | ✅ | `GET /api/v1/categories` |
| `POST productsByCategory` (`categoryId`) | ✅ | `GET /api/v1/categories/{id}/products` |
| `POST commissionBusiness` (`businessid`) | ✅ | `GET /api/v1/businesses/{id}/commissions` |
| `PUT commissionBusiness` (`businessid`, `commissionin`) | ✅ | `POST /api/v1/businesses/{id}/commissions` |
| `PATCH commissionBusiness` (`commissionbusinessid`, `commissionin`) | ✅ | `PATCH /api/v1/commissions/{id}` |

La API key sigue viajando en el header `key` y validándose con `gestopago.validate_token`.
El segmento `PRODUCTION`/`DEVELOP` sigue eligiendo la conexión de BD
(`GESTOPAGO_LEGACY_*_CONNECTION`).

## Diferencias de comportamiento (intencionales)

| Antes | Ahora | Por qué |
|---|---|---|
| SQL armado con interpolación de strings | Parámetros enlazados | Inyección SQL en todos los endpoints |
| Sin timeout (`CURLOPT_TIMEOUT => 0`) | 10 s conexión / 60 s total | Un proveedor colgado bloqueaba workers |
| Si fallaba el registro en BD, el pago se enviaba igual | Se aborta antes de cobrar | Trazabilidad de cada cargo |
| Aviso de saldo síncrono dentro del pago | Job en cola con 3 reintentos | Un fallo del core devolvía 500 tras cobrar |
| `getTransaccionsByClient` sin `bussinessid` devolvía `false` | `422` con errores de validación | Contrato explícito |
| `confirmTx`/`responseTx` con UPC inexistente: error de PHP | `500` con el mensaje genérico heredado | Igual para el cliente, sin warnings |
| `encryptData` y `getJWT` abiertos en producción | `404` en producción | Exponían el JWT del distribuidor |
| CORS `*` con credenciales | `config/cors.php` (por defecto `*` sin credenciales) | Combinación inválida en navegadores |
| Sin rate limit | 120 req/min por API key (`API_RATE_LIMIT`) | Protege el saldo con el proveedor |
| XML con `'` reemplazado por `"` antes de guardarse | XML original | Era un parche por la interpolación SQL |

## Variables de entorno

| wsgestopago | gestopago-api |
|---|---|
| `URL_BASE` | `GESTOPAGO_BASE_URL` |
| `URL_TOKEN` | `GESTOPAGO_TOKEN_PATH` |
| `SEND_ECHO` | `GESTOPAGO_ECHO_PATH` |
| `VALIDATEME` | `GESTOPAGO_VALIDATE_ME_PATH` |
| `GETBALANCE` | `GESTOPAGO_BALANCE_PATH` |
| `GETPRODUCTS` | `GESTOPAGO_PRODUCTS_PATH` |
| `SENDTX` | `GESTOPAGO_SEND_TX_PATH` |
| `CONFIRMTX` | `GESTOPAGO_CONFIRM_TX_PATH` |
| `VERIFYREFERENCE` | `GESTOPAGO_VERIFY_REFERENCE_PATH` |
| `idDistribuidor` | `GESTOPAGO_DISTRIBUTOR_ID` |
| `codigoDispositivo` | `GESTOPAGO_DEVICE_CODE` |
| `password` | `GESTOPAGO_PASSWORD` |
| `SECRET_KEY` | `GESTOPAGO_SECRET_KEY` |
| `SECRET_IV` | `GESTOPAGO_SECRET_IV` |
| `URL_CORE` (+ `gp/avisosaldo`) | `GESTOPAGO_LOW_BALANCE_NOTIFY_URL` (URL completa) |
| conexiones `PRODUCTION` / `DEVELOP` en `config/database.php` (no versionado) | `DB_*` y `DB_DEVELOP_*` |

## Base de datos

- El esquema productivo **no se modifica**. `php artisan migrate` solo crea las tablas propias de
  Laravel (`cache`, `jobs`, `job_batches`, `failed_jobs`) en el esquema `public`.
- `database/sql/gestopago_schema.sql` es una **reconstrucción** para local/CI: el DDL real nunca se
  versionó. Reemplázalo por `pg_dump --schema-only --schema=gestopago` de producción en cuanto haya
  acceso, y vuelve a correr la suite `pgsql` contra él.
- Mientras convivan ambos servicios usa `GESTOPAGO_TOKEN_STORE=database` para compartir el JWT del
  proveedor a través de `gestopago.insert_token_gestopago` / `get_token_gestopago_avaible`.

## Plan de corte sugerido

1. Desplegar `gestopago-api` en paralelo apuntando a la BD de desarrollo; correr `bruno/` contra el
   sandbox (`/api/DEVELOP/*` y `/api/v1/*`).
2. Configurar `GESTOPAGO_*` de producción, `GESTOPAGO_TOKEN_STORE=database`,
   `GESTOPAGO_DIAGNOSTICS_ENABLED=false`, `API_DOCS_ENABLED=false`.
3. Cambiar el DNS/proxy de wsgestopago al nuevo servicio (mismas rutas). Monitorear 401/422/502.
4. Migrar clientes a `/api/v1/*` (app RN `helmmxfarma`, `helmweb`); después,
   `GESTOPAGO_LEGACY_ROUTES=false` y `GESTOPAGO_TOKEN_STORE=cache`.
5. Archivar el repo `wsgestopago`.
