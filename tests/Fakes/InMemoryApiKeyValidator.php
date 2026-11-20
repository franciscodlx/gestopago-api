<?php

namespace Tests\Fakes;

use App\Domain\GestoPago\Contracts\ApiKeyValidator;

final readonly class InMemoryApiKeyValidator implements ApiKeyValidator
{
    /**
     * @param  list<string>  $validKeys
     */
    public function __construct(private array $validKeys) {}

    public function isValid(string $apiKey): bool
    {
        return in_array($apiKey, $this->validKeys, true);
    }
}
