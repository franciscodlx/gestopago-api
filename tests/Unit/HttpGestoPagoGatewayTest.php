<?php

use App\Domain\GestoPago\Contracts\GestoPagoGateway;
use App\Domain\GestoPago\Exceptions\GatewayUnavailableException;
use App\Domain\GestoPago\Exceptions\MisconfiguredGatewayException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\GestoPagoFake;

it('pide el token con las credenciales y lo reutiliza', function (): void {
    GestoPagoFake::fake(['balance' => GestoPagoFake::simpleXml()]);
    $gateway = app(GestoPagoGateway::class);

    $gateway->balance();
    $gateway->balance();

    Http::assertSentCount(3);
    Http::assertSent(function (Request $request): bool {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return str_contains($request->url(), '/token') && $query === [
            'idDistribuidor' => '1',
            'codigoDispositivo' => 'TEST-DEVICE',
            'password' => 'test-password',
        ];
    });
});

it('renueva el token y reintenta una vez ante un 401', function (): void {
    Http::fake([
        GestoPagoFake::BASE_URL.'/token*' => Http::sequence()->push(['token' => 'viejo'])->push(['token' => 'nuevo']),
        GestoPagoFake::BASE_URL.'/echo' => Http::sequence()->push('', 401)->push(GestoPagoFake::simpleXml()),
    ]);

    expect(app(GestoPagoGateway::class)->echo()->code())->toBe('01');

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/echo') && $request->hasHeader('Authorization', 'Bearer nuevo'));
});

it('traduce errores de conexión a GatewayUnavailableException', function (): void {
    Http::fake([GestoPagoFake::BASE_URL.'/*' => Http::failedConnection()]);

    app(GestoPagoGateway::class)->echo();
})->throws(GatewayUnavailableException::class, 'sin conexión');

it('falla si el endpoint de token no devuelve token', function (): void {
    Http::fake([GestoPagoFake::BASE_URL.'/token*' => Http::response(['error' => 'credenciales'])]);

    app(GestoPagoGateway::class)->accessToken();
})->throws(GatewayUnavailableException::class);

it('falla con un mensaje claro si falta la URL base', function (): void {
    config(['gestopago.base_url' => '']);

    app(GestoPagoGateway::class)->echo();
})->throws(MisconfiguredGatewayException::class, 'base_url');
