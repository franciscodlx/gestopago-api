---
paths:
  - "tests/**"
---

# Tests

- Pest 4. `tests/Unit` y `tests/Feature` no tocan BD: usa `$this->useInMemoryPersistence()` y `GestoPagoFake::fake([...])`.
- `Http::preventStrayRequests()` está activo: toda llamada externa debe estar simulada.
- Para inspeccionar lo enviado al proveedor usa `GestoPagoFake::decryptSigned($request)`.
- `tests/Integration` (grupo `pgsql`) prueba los repositorios contra PostgreSQL con el esquema real de
  referencia; cada test corre en una transacción que se revierte.
- Nombres de tests en español describiendo el comportamiento ("responde 422 cuando ...").
- Cobertura mínima en CI: 90 % (suite completa).
