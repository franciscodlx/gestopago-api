<?php

use Tests\Support\GestoPagoFake;

beforeEach(fn () => $this->useInMemoryPersistence());

it('sincroniza el catálogo y lista productos por categoría', function (): void {
    GestoPagoFake::fake(['products' => GestoPagoFake::productsXml()]);

    $this->postJson('/api/v1/catalog/sync', [], $this->apiHeaders())->assertOk()->assertJsonPath('data.synced', 2);

    $this->getJson('/api/v1/categories/2/products', $this->apiHeaders())
        ->assertOk()
        ->assertJsonPath('data.0.id_producto', 20);
});

it('lista categorías', function (): void {
    $this->getJson('/api/v1/categories', $this->apiHeaders())->assertOk()->assertJsonPath('data.0.name', 'Recargas');
});

it('crea, consulta y actualiza comisiones', function (): void {
    $this->postJson('/api/v1/businesses/7/commissions', ['commission' => 3.5], $this->apiHeaders())
        ->assertCreated()
        ->assertJsonPath('data.0.commission', '3.5');

    $this->patchJson('/api/v1/commissions/1', ['commission' => '4'], $this->apiHeaders())
        ->assertOk()
        ->assertJsonPath('data.0.commission', '4');

    $this->getJson('/api/v1/businesses/7/commissions', $this->apiHeaders())
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('exige la comisión', function (): void {
    $this->postJson('/api/v1/businesses/7/commissions', [], $this->apiHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['commission']);
});

it('consulta eco, validateMe y saldo', function (): void {
    GestoPagoFake::fake([
        'echo' => GestoPagoFake::simpleXml('01', 'eco'),
        'validate_me' => GestoPagoFake::simpleXml('01', 'me'),
        'balance' => '<R><SALDO>1500.00</SALDO></R>',
    ]);

    $this->getJson('/api/v1/gateway/echo', $this->apiHeaders())->assertOk()->assertJsonPath('data.MENSAJE.TEXTO', 'eco');
    $this->getJson('/api/v1/gateway/me', $this->apiHeaders())->assertOk()->assertJsonPath('data.MENSAJE.TEXTO', 'me');
    $this->getJson('/api/v1/gateway/balance', $this->apiHeaders())->assertOk()->assertJsonPath('data.SALDO', '1500.00');
});
