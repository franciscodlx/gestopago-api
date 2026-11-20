<?php

use App\Domain\GestoPago\Exceptions\GestoPagoException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Errores esperados del proveedor: no ensucian el log como "error".
        $exceptions->dontReport([GestoPagoException::class]);
        $exceptions->reportable(function (GestoPagoException $exception): void {
            logger()->warning($exception->getMessage(), ['exception' => $exception::class, ...$exception->context()]);
        });

        $exceptions->render(function (GestoPagoException $exception) {
            $message = $exception->status() >= 500 && ! config('app.debug')
                ? 'No fue posible completar la operación con Gesto Pago.'
                : $exception->getMessage();

            return response()->json(['message' => $message, ...$exception->context()], $exception->status());
        });
    })->create();
