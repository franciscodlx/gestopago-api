<?php

namespace App\Infrastructure\Persistence;

use App\Domain\GestoPago\Contracts\ApiKeyValidator;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionResolverInterface;

/**
 * Decorador que cachea solo las validaciones positivas, por conexión de BD, para
 * no consultar PostgreSQL en cada request. Las keys nunca se guardan en claro.
 */
final readonly class CachedApiKeyValidator implements ApiKeyValidator
{
    public function __construct(
        private ApiKeyValidator $inner,
        private Cache $cache,
        private ConnectionResolverInterface $db,
        private int $ttlSeconds,
    ) {}

    public function isValid(string $apiKey): bool
    {
        if ($this->ttlSeconds <= 0) {
            return $this->inner->isValid($apiKey);
        }

        $cacheKey = sprintf('api-key:%s:%s', $this->db->getDefaultConnection(), hash('sha256', $apiKey));

        if ($this->cache->get($cacheKey) === true) {
            return true;
        }

        $isValid = $this->inner->isValid($apiKey);

        if ($isValid) {
            $this->cache->put($cacheKey, true, $this->ttlSeconds);
        }

        return $isValid;
    }
}
