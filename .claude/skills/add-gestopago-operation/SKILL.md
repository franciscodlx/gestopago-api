---
name: add-gestopago-operation
description: Agrega una nueva operación de la API de Gesto Pago de punta a punta (config, gateway, caso de uso, endpoint v1, tests, OpenAPI y Bruno). Úsalo cuando haya que exponer un endpoint nuevo del proveedor.
argument-hint: "<nombreOperacion> [descripción]"
---

# Agregar una operación de Gesto Pago

Operación solicitada: $ARGUMENTS

1. **Config**: añade la ruta en `config/gestopago.php` (`endpoints.<snake>` ← `GESTOPAGO_<SNAKE>_PATH`),
   en `.env.example` y en `phpunit.xml` (con `force="true"`) y en `tests/Support/GestoPagoFake::fake()`.
2. **Puerto**: método en `app/Domain/GestoPago/Contracts/GestoPagoGateway.php` que devuelva `GatewayResponse`.
3. **Adaptador**: implementa en `HttpGestoPagoGateway` con `$this->call('<snake>', $payloadJson, idempotent: <bool>)`.
   Solo `idempotent: true` si repetir la llamada no puede cobrar dos veces.
4. **Caso de uso** (si hay reglas): clase `final readonly` en `app/Application/GestoPago`.
5. **HTTP v1**: FormRequest en `app/Http/Requests/Api/V1`, acción en un controlador de `Api/V1` con
   PHPDoc (título + descripción, lo usa Scramble), ruta con nombre en `routes/api.php`.
6. **Tests**: unit del caso de uso + feature del endpoint con `GestoPagoFake`, verificando el payload
   descifrado con `GestoPagoFake::decryptSigned()`.
7. **Docs**: `php artisan scramble:export --path=docs/openapi.json` y una request `.bru` en `bruno/`.
8. Ejecuta el skill `quality-gate`.
