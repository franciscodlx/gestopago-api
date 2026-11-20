<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Suites
|--------------------------------------------------------------------------
|
| Unit         → clases aisladas (con contenedor de Laravel para config/HTTP fake).
| Feature      → HTTP de punta a punta con el proveedor simulado (Http::fake)
|                y repositorios en memoria. No requieren base de datos.
| Integration  → repositorios contra PostgreSQL real con el esquema de
|                database/sql/gestopago_schema.sql (grupo "pgsql").
|
*/

pest()->extend(TestCase::class)->in('Unit', 'Feature', 'Integration');

pest()->group('pgsql')->in('Integration');
