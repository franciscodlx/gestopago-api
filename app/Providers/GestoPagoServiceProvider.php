<?php

namespace App\Providers;

use App\Domain\GestoPago\Contracts\AccessTokenStore;
use App\Domain\GestoPago\Contracts\ApiKeyValidator;
use App\Domain\GestoPago\Contracts\CatalogRepository;
use App\Domain\GestoPago\Contracts\CommissionRepository;
use App\Domain\GestoPago\Contracts\GestoPagoGateway;
use App\Domain\GestoPago\Contracts\LowBalanceNotifier;
use App\Domain\GestoPago\Contracts\PayloadCipher;
use App\Domain\GestoPago\Contracts\TransactionRepository;
use App\Infrastructure\GestoPago\AesPayloadCipher;
use App\Infrastructure\GestoPago\CacheAccessTokenStore;
use App\Infrastructure\GestoPago\HttpGestoPagoGateway;
use App\Infrastructure\GestoPago\HttpLowBalanceNotifier;
use App\Infrastructure\Persistence\CachedApiKeyValidator;
use App\Infrastructure\Persistence\DatabaseAccessTokenStore;
use App\Infrastructure\Persistence\PostgresApiKeyValidator;
use App\Infrastructure\Persistence\PostgresCatalogRepository;
use App\Infrastructure\Persistence\PostgresCommissionRepository;
use App\Infrastructure\Persistence\PostgresTransactionRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Une los puertos del dominio con sus adaptadores. "scoped" = una instancia por
 * request/job, seguro también bajo Octane/FrankenPHP worker mode.
 */
final class GestoPagoServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $scopedBindings = [
        GestoPagoGateway::class => HttpGestoPagoGateway::class,
        PayloadCipher::class => AesPayloadCipher::class,
        LowBalanceNotifier::class => HttpLowBalanceNotifier::class,
        TransactionRepository::class => PostgresTransactionRepository::class,
        CatalogRepository::class => PostgresCatalogRepository::class,
        CommissionRepository::class => PostgresCommissionRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->scopedBindings as $contract => $implementation) {
            $this->app->scoped($contract, $implementation);
        }

        $this->app->scoped(AccessTokenStore::class, fn (Application $app): AccessTokenStore => match ($app->make('config')->get('gestopago.token.store')) {
            'database' => $app->make(DatabaseAccessTokenStore::class),
            default => $app->make(CacheAccessTokenStore::class),
        });

        $this->app->scoped(ApiKeyValidator::class, fn (Application $app): ApiKeyValidator => new CachedApiKeyValidator(
            inner: $app->make(PostgresApiKeyValidator::class),
            cache: $app->make('cache.store'),
            db: $app->make('db'),
            ttlSeconds: (int) $app->make('config')->get('gestopago.api_keys.cache_ttl'),
        ));
    }
}
