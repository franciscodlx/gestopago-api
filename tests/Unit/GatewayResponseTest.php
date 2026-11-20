<?php

use App\Domain\GestoPago\Data\GatewayResponse;

it('devuelve null cuando no hay leyenda', function (): void {
    expect((new GatewayResponse([], '<R/>'))->legend())->toBeNull();
});

it('lee código y texto de MENSAJE', function (): void {
    $response = new GatewayResponse(['MENSAJE' => ['CODIGO' => '05', 'TEXTO' => 'Sin saldo']], '');

    expect($response->code())->toBe('05')->and($response->message())->toBe('Sin saldo');
});
