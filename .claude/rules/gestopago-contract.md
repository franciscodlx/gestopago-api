---
paths:
  - "app/Infrastructure/GestoPago/**"
  - "app/Application/GestoPago/**"
  - "app/Http/Controllers/Legacy/**"
  - "database/sql/**"
---

# Contrato con Gesto Pago y con los clientes heredados

- Token: `POST {base}{token}?idDistribuidor&codigoDispositivo&password` → JSON `{token}`; vive 24 h (cache 23 h).
- Operaciones con datos: multipart, campo `signed` = base64(AES-256-CBC(JSON)) con llave/IV del proveedor.
- Respuestas en XML; éxito = `MENSAJE.CODIGO == "01"`. `XmlResponseParser` reproduce la forma de
  `json_encode(SimpleXMLElement)` (atributos en `@attributes`) porque los clientes dependen de ella.
- `sendTx` agrega `unidad=assert`, `upc` (14 hex) y `horaLocal` (YmdHis, America/Mexico_City).
- Saldo `< 5000` tras un pago → job `NotifyLowBalance` (nunca debe hacer fallar el pago).
- Nombres con erratas en BD y en el contrato heredado se conservan: `insert_trasaction`,
  `get_token_gestopago_avaible`, `bussinessid`, `LEGENT`, `getTransaccionsByClient`.
- Cualquier cambio aquí exige test en `tests/Feature/Legacy` o `tests/Unit` que fije el contrato.
