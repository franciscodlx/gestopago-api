<?php

beforeEach(fn () => $this->useInMemoryPersistence());

it('rechaza peticiones sin API key', function (): void {
    $this->getJson('/api/v1/categories')->assertUnauthorized()->assertJsonPath('message', 'API key ausente o inválida.');
});

it('rechaza API keys inválidas', function (): void {
    $this->getJson('/api/v1/categories', ['X-Api-Key' => 'otra'])->assertUnauthorized();
});

it('acepta la key en X-Api-Key o en el header heredado "key"', function (string $header): void {
    $this->getJson('/api/v1/categories', [$header => self::API_KEY])->assertOk();
})->with(['X-Api-Key', 'key']);

it('expone health check sin autenticación', function (): void {
    $this->get('/up')->assertOk();
});

it('limita la tasa de peticiones por API key', function (): void {
    config(['services.api.rate_limit' => 2]);

    $this->getJson('/api/v1/categories', $this->apiHeaders())->assertOk();
    $this->getJson('/api/v1/categories', $this->apiHeaders())->assertOk();
    $this->getJson('/api/v1/categories', $this->apiHeaders())->assertTooManyRequests();
});
