<?php

use Illuminate\Support\Facades\Http;
use Tests\Support\GestoPagoFake;

beforeEach(fn () => $this->useInMemoryPersistence());

it('sincroniza el catálogo desde la consola', function (): void {
    GestoPagoFake::fake(['products' => GestoPagoFake::productsXml()]);

    $this->artisan('gestopago:sync-products')
        ->expectsOutputToContain('Productos sincronizados: 2')
        ->assertSuccessful();
});

it('está programado a diario', function (): void {
    $this->artisan('schedule:list')->expectsOutputToContain('gestopago:sync-products')->assertSuccessful();
});

it('falla si el proveedor no responde', function (): void {
    GestoPagoFake::fake(['products' => Http::response('', 503)]);

    $this->artisan('gestopago:sync-products');
})->throws(App\Domain\GestoPago\Exceptions\GatewayUnavailableException::class);
