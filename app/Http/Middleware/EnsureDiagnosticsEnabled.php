<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Los endpoints de diagnóstico exponen el JWT del proveedor y cifran payloads
 * arbitrarios: solo existen con GESTOPAGO_DIAGNOSTICS_ENABLED y fuera de producción.
 */
final class EnsureDiagnosticsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('gestopago.diagnostics_enabled') && ! app()->isProduction(), 404);

        return $next($request);
    }
}
