<?php

use App\Domain\GestoPago\Exceptions\GatewayUnavailableException;
use App\Infrastructure\GestoPago\XmlResponseParser;
use Tests\Support\GestoPagoFake;

it('convierte el XML conservando atributos y CDATA', function (): void {
    $response = (new XmlResponseParser)->parse(GestoPagoFake::successfulPaymentXml(withPin: true));

    expect($response->code())->toBe('01')
        ->and($response->get('ID_TX'))->toBe('987654')
        ->and($response->get('MENSAJE.PIN'))->toBe('1234-5678')
        ->and($response->legend())->toBe('Conserve su ticket');
});

it('expone los atributos bajo @attributes como en Lumen', function (): void {
    $response = (new XmlResponseParser)->parse(GestoPagoFake::productsXml());

    expect($response->get('PRODUCTOS.producto.0.@attributes.idProducto'))->toBe('10');
});

it('rechaza respuestas vacías o que no son XML', function (string $body): void {
    (new XmlResponseParser)->parse($body);
})->throws(GatewayUnavailableException::class)->with(['', '   ', '<html><body>502', '{"error":true}']);
