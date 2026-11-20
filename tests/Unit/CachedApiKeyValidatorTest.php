<?php

use App\Domain\GestoPago\Contracts\ApiKeyValidator;
use App\Infrastructure\Persistence\CachedApiKeyValidator;
use Illuminate\Support\Facades\Cache;

function countingValidator(bool $result): ApiKeyValidator
{
    return new class($result) implements ApiKeyValidator
    {
        public int $calls = 0;

        public function __construct(private readonly bool $result) {}

        public function isValid(string $apiKey): bool
        {
            $this->calls++;

            return $this->result;
        }
    };
}

it('cachea solo las validaciones positivas y sin guardar la key en claro', function (): void {
    $inner = countingValidator(true);
    $validator = new CachedApiKeyValidator($inner, Cache::store(), app('db'), 60);

    expect($validator->isValid('secreta'))->toBeTrue()
        ->and($validator->isValid('secreta'))->toBeTrue()
        ->and($inner->calls)->toBe(1)
        ->and(Cache::has('api-key:'.app('db')->getDefaultConnection().':'.hash('sha256', 'secreta')))->toBeTrue();
});

it('no cachea keys inválidas', function (): void {
    $inner = countingValidator(false);
    $validator = new CachedApiKeyValidator($inner, Cache::store(), app('db'), 60);

    $validator->isValid('mala');
    $validator->isValid('mala');

    expect($inner->calls)->toBe(2);
});

it('con TTL 0 delega siempre', function (): void {
    $inner = countingValidator(true);
    $validator = new CachedApiKeyValidator($inner, Cache::store(), app('db'), 0);

    $validator->isValid('k');
    $validator->isValid('k');

    expect($inner->calls)->toBe(2);
});
