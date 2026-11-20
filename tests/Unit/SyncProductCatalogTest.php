<?php

use App\Application\GestoPago\SyncProductCatalog;
use Tests\Support\GestoPagoFake;

beforeEach(fn () => $this->useInMemoryPersistence());

it('guarda cada producto del catálogo', function (): void {
    GestoPagoFake::fake(['products' => GestoPagoFake::productsXml()]);

    expect(app(SyncProductCatalog::class)->handle())->toBe(2)
        ->and($this->catalog->products[20]->service)->toBe('CFE')
        ->and($this->catalog->products[20]->hasCheckDigit)->toBe('true');
});

it('soporta un catálogo con un solo producto', function (): void {
    GestoPagoFake::fake(['products' => '<R><PRODUCTOS><producto servicio="A" producto="B" idServicio="1" idProducto="5" idCatTipoServicio="1" tipoFront="1" hasDigitoVerificador="false" precio="1" showAyuda="false" tipoReferencia="x"/></PRODUCTOS></R>']);

    expect(app(SyncProductCatalog::class)->handle())->toBe(1)
        ->and($this->catalog->products)->toHaveKey(5);
});
