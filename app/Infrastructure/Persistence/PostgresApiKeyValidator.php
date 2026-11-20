<?php

namespace App\Infrastructure\Persistence;

use App\Domain\GestoPago\Contracts\ApiKeyValidator;

final readonly class PostgresApiKeyValidator implements ApiKeyValidator
{
    public function __construct(private StoredFunctions $functions) {}

    public function isValid(string $apiKey): bool
    {
        return filter_var($this->functions->value('validate_token', [$apiKey]), FILTER_VALIDATE_BOOLEAN);
    }
}
