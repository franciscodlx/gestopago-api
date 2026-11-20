# Seguridad

- No leas ni muestres `.env`, `.env.production` ni secretos de CI. Usa `.env.example` como referencia.
- Las credenciales del sandbox y las llaves AES nunca van a código, tests, Bruno ni docs (los tests usan llaves propias en `phpunit.xml`).
- Los endpoints de diagnóstico (`/api/v1/diagnostics/*`, `encryptData`, `getJWT`) deben seguir bloqueados en producción.
- API keys: nunca registrarlas en logs; en caché solo su SHA-256.
- XML del proveedor: `LIBXML_NONET`, sin `LIBXML_NOENT`.
