---
paths:
  - "app/**/*.php"
---

# Arquitectura (puertos y adaptadores)

- Dependencias solo hacia dentro: `Http` → `Application` → `Domain` ← `Infrastructure`.
  `Domain` no importa nada de `Illuminate\Http`, `DB` ni facades.
- Un caso de uso = una clase `final readonly` en `app/Application/GestoPago` con un método `handle()`.
- Controladores delgados: validan con FormRequest, llaman un caso de uso o un puerto y devuelven JSON `{ "data": ... }`.
- Nuevo adaptador → interfaz en `Domain/GestoPago/Contracts` + binding `scoped` en `GestoPagoServiceProvider`
  (scoped, no singleton: debe ser seguro en Octane/FrankenPHP worker mode).
- Errores de negocio: subclase de `GestoPagoException` con `status()`; el render está en `bootstrap/app.php`.
- Usa `final`, constructor promotion, tipos de retorno explícitos y array shapes en PHPDoc. Larastan nivel 8 sin baseline.
