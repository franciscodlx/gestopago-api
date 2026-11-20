<?php

namespace App\Providers;

use App\Http\Middleware\AuthenticateApiKey;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureApiDocs();
    }

    /**
     * Límite por API key (o IP si no hay key) para proteger el saldo con el proveedor.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $apiKey = $request->header(AuthenticateApiKey::HEADER) ?? $request->header('key');
            $identity = is_string($apiKey) && $apiKey !== '' ? hash('sha256', $apiKey) : $request->ip();

            return Limit::perMinute((int) config('services.api.rate_limit', 120))->by((string) $identity);
        });
    }

    /**
     * OpenAPI 3.1 generado por Scramble: /docs/api (UI) y /docs/api.json.
     * Swagger UI en /docs/swagger. Fuera de local requiere API_DOCS_ENABLED=true.
     */
    private function configureApiDocs(): void
    {
        Gate::define('viewApiDocs', fn ($user = null): bool => (bool) config('scramble.enabled_outside_local'));

        Scramble::configure()
            ->routes(fn ($route): bool => str_starts_with($route->uri(), 'api/v1'))
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(SecurityScheme::apiKey('header', AuthenticateApiKey::HEADER));
            });
    }
}
