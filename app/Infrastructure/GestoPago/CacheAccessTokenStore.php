<?php

namespace App\Infrastructure\GestoPago;

use App\Domain\GestoPago\Contracts\AccessTokenStore;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * JWT del proveedor en la caché de Laravel (Redis en producción, compartida entre réplicas).
 */
final readonly class CacheAccessTokenStore implements AccessTokenStore
{
    private const string KEY = 'gestopago:access-token';

    public function __construct(
        private Cache $cache,
        private GestoPagoSettings $settings,
    ) {}

    public function get(): ?string
    {
        $token = $this->cache->get(self::KEY);

        return is_string($token) ? $token : null;
    }

    public function put(string $token): void
    {
        $this->cache->put(self::KEY, $token, $this->settings->tokenTtlSeconds());
    }

    public function forget(): void
    {
        $this->cache->forget(self::KEY);
    }
}
