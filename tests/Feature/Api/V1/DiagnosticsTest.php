<?php

use App\Domain\GestoPago\Contracts\PayloadCipher;
use Tests\Support\GestoPagoFake;

beforeEach(fn () => $this->useInMemoryPersistence());

it('cifra payloads y muestra el token en sandbox', function (): void {
    GestoPagoFake::fake();

    $signed = $this->postJson('/api/v1/diagnostics/encrypt', ['payload' => ['a' => 1]], $this->apiHeaders())
        ->assertOk()
        ->json('data.signed');

    expect(app(PayloadCipher::class)->decrypt($signed))->toBe('{"a":1}');

    $this->getJson('/api/v1/diagnostics/token', $this->apiHeaders())->assertOk()->assertJsonPath('data.token', GestoPagoFake::TOKEN);
});

it('no existen cuando están deshabilitados', function (): void {
    config(['gestopago.diagnostics_enabled' => false]);

    $this->getJson('/api/v1/diagnostics/token', $this->apiHeaders())->assertNotFound();
});

it('no existen en producción aunque estén habilitados', function (): void {
    $this->app->detectEnvironment(fn (): string => 'production');

    $this->getJson('/api/v1/diagnostics/token', $this->apiHeaders())->assertNotFound();
    $this->postJson('/api/DEVELOP/getJWT', [], $this->apiHeaders())->assertNotFound();
});
