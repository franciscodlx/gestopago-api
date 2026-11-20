<?php

/*
|--------------------------------------------------------------------------
| Gesto Pago
|--------------------------------------------------------------------------
|
| Configuración del agregador de pagos de servicios Gesto Pago. Todos los
| valores sensibles vienen del entorno; nunca se versionan.
|
*/

return [

    'base_url' => rtrim((string) env('GESTOPAGO_BASE_URL', ''), '/'),

    /*
     * Rutas relativas a base_url de cada operación del proveedor.
     */
    'endpoints' => [
        'token' => env('GESTOPAGO_TOKEN_PATH', ''),
        'echo' => env('GESTOPAGO_ECHO_PATH', ''),
        'validate_me' => env('GESTOPAGO_VALIDATE_ME_PATH', ''),
        'balance' => env('GESTOPAGO_BALANCE_PATH', ''),
        'products' => env('GESTOPAGO_PRODUCTS_PATH', ''),
        'send_transaction' => env('GESTOPAGO_SEND_TX_PATH', ''),
        'confirm_transaction' => env('GESTOPAGO_CONFIRM_TX_PATH', ''),
        'verify_reference' => env('GESTOPAGO_VERIFY_REFERENCE_PATH', ''),
    ],

    'credentials' => [
        'distributor_id' => env('GESTOPAGO_DISTRIBUTOR_ID'),
        'device_code' => env('GESTOPAGO_DEVICE_CODE'),
        'password' => env('GESTOPAGO_PASSWORD'),
    ],

    /*
     * AES-256-CBC. Ambos valores en base64 tal como los entrega Gesto Pago.
     */
    'encryption' => [
        'key' => env('GESTOPAGO_SECRET_KEY'),
        'iv' => env('GESTOPAGO_SECRET_IV'),
    ],

    'http' => [
        'connect_timeout' => (int) env('GESTOPAGO_CONNECT_TIMEOUT', 10),
        'timeout' => (int) env('GESTOPAGO_TIMEOUT', 60),
        /* Reintentos solo para operaciones idempotentes (token, eco, saldo, catálogo). */
        'retries' => (int) env('GESTOPAGO_RETRIES', 2),
        'retry_delay_ms' => (int) env('GESTOPAGO_RETRY_DELAY_MS', 300),
    ],

    'token' => [
        /* cache | database (funciones gestopago.*_token_gestopago del esquema heredado) */
        'store' => env('GESTOPAGO_TOKEN_STORE', 'cache'),
        /* El JWT del proveedor vive 24 h; se renueva antes para evitar carreras. */
        'ttl_seconds' => (int) env('GESTOPAGO_TOKEN_TTL', 23 * 3600),
    ],

    'transaction' => [
        /* Valor fijo que exige el proveedor en el campo "unidad". */
        'unit' => env('GESTOPAGO_TX_UNIT', 'assert'),
        'timezone' => env('GESTOPAGO_TIMEZONE', 'America/Mexico_City'),
    ],

    'low_balance' => [
        'threshold' => (float) env('GESTOPAGO_LOW_BALANCE_THRESHOLD', 5000),
        /* Endpoint del core que envía el correo de saldo insuficiente (vacío = desactivado). */
        'notify_url' => env('GESTOPAGO_LOW_BALANCE_NOTIFY_URL', ''),
    ],

    'api_keys' => [
        /* Segundos que se cachea una API key válida (0 = sin caché). */
        'cache_ttl' => (int) env('API_KEY_CACHE_TTL', 60),
    ],

    /*
     * Endpoints de diagnóstico (cifrar payloads, ver el JWT del proveedor).
     * Nunca deben habilitarse en producción.
     */
    'diagnostics_enabled' => (bool) env('GESTOPAGO_DIAGNOSTICS_ENABLED', false),

    /*
     * Compatibilidad con wsgestopago (Lumen): /api/{PRODUCTION|DEVELOP}/<operación>.
     * Cada segmento se mapea a una conexión de base de datos.
     */
    'legacy' => [
        'enabled' => (bool) env('GESTOPAGO_LEGACY_ROUTES', true),
        'connections' => [
            'PRODUCTION' => env('GESTOPAGO_LEGACY_PRODUCTION_CONNECTION', 'pgsql'),
            'DEVELOP' => env('GESTOPAGO_LEGACY_DEVELOP_CONNECTION', 'pgsql_develop'),
        ],
    ],

];
