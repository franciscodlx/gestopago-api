<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\Support\GestoPagoFake;

beforeEach(function (): void {
    $this->useInMemoryPersistence();
    Bus::fake();
});

it('crea un pago y devuelve 201 con la autorización', function (): void {
    GestoPagoFake::fake(['send_transaction' => GestoPagoFake::successfulPaymentXml(withPin: true)]);

    $response = $this->postJson('/api/v1/payments', [
        'businessId' => 7,
        'idServicio' => 1,
        'idProducto' => 10,
        'telefono' => '5512345678',
    ], $this->apiHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.transactionId', '987654')
        ->assertJsonPath('data.authorizationNumber', 'A1B2C3')
        ->assertJsonPath('data.pin', '1234-5678')
        ->assertJsonPath('data.legend', 'Conserve su ticket');

    expect($response->json('data.upc'))->toMatch('/^[a-f0-9]{14}$/');
});

it('valida el cuerpo del pago', function (): void {
    $this->postJson('/api/v1/payments', ['telefono' => '123'], $this->apiHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['businessId', 'idServicio', 'idProducto', 'telefono']);
});

it('no reenvía al proveedor campos fuera del contrato', function (): void {
    GestoPagoFake::fake(['send_transaction' => GestoPagoFake::successfulPaymentXml()]);

    $this->postJson('/api/v1/payments', [
        'businessId' => 7, 'idServicio' => 1, 'idProducto' => 10, 'inyectado' => 'x',
    ], $this->apiHeaders())->assertCreated();

    Http::assertSent(fn ($request): bool => ! str_ends_with($request->url(), '/sendTx')
        || ! array_key_exists('inyectado', GestoPagoFake::decryptSigned($request)));
});

it('responde 422 con el upc cuando el proveedor rechaza', function (): void {
    GestoPagoFake::fake(['send_transaction' => GestoPagoFake::rejectedPaymentXml()]);

    $this->postJson('/api/v1/payments', ['businessId' => 7, 'idServicio' => 1, 'idProducto' => 10], $this->apiHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Referencia invalida')
        ->assertJsonPath('code', '14')
        ->assertJsonStructure(['message', 'upc', 'code']);
});

it('responde 502 sin filtrar detalles cuando el proveedor no está disponible', function (): void {
    config(['app.debug' => false]);
    GestoPagoFake::fake(['send_transaction' => Http::response('down', 503)]);

    $this->postJson('/api/v1/payments', ['businessId' => 7, 'idServicio' => 1, 'idProducto' => 10], $this->apiHeaders())
        ->assertStatus(502)
        ->assertJsonPath('message', 'No fue posible completar la operación con Gesto Pago.');
});

it('consulta la respuesta registrada y confirma con la solicitud original', function (): void {
    GestoPagoFake::fake([
        'send_transaction' => GestoPagoFake::successfulPaymentXml(),
        'confirm_transaction' => GestoPagoFake::simpleXml('01', 'Confirmada'),
    ]);

    $upc = $this->postJson('/api/v1/payments', ['businessId' => 7, 'idServicio' => 1, 'idProducto' => 10], $this->apiHeaders())
        ->json('data.upc');

    $this->getJson("/api/v1/payments/{$upc}", $this->apiHeaders())
        ->assertOk()
        ->assertJsonPath('data.ID_TX', '987654');

    $this->postJson("/api/v1/payments/{$upc}/confirm", [], $this->apiHeaders())
        ->assertOk()
        ->assertJsonPath('data.MENSAJE.TEXTO', 'Confirmada');

    Http::assertSent(fn ($request): bool => ! str_ends_with($request->url(), '/confirmTx')
        || GestoPagoFake::decryptSigned($request)['upc'] === $upc);
});

it('responde 404 para UPCs desconocidos o mal formados', function (string $upc): void {
    $this->getJson("/api/v1/payments/{$upc}", $this->apiHeaders())->assertNotFound();
})->with(['aaaaaaaaaaaaaa', 'no-es-un-upc']);

it('verifica referencias con el proveedor', function (): void {
    GestoPagoFake::fake(['verify_reference' => GestoPagoFake::simpleXml('01', 'Referencia valida')]);

    $this->postJson('/api/v1/references/verify', ['idServicio' => 2, 'referencia' => '0123456789'], $this->apiHeaders())
        ->assertOk()
        ->assertJsonPath('data.MENSAJE.TEXTO', 'Referencia valida');

    Http::assertSent(fn ($request): bool => ! str_ends_with($request->url(), '/verifyReference')
        || GestoPagoFake::decryptSigned($request) === ['idServicio' => 2, 'referencia' => '0123456789']);
});

it('lista transacciones de un negocio validando el rango', function (): void {
    $this->getJson('/api/v1/businesses/7/transactions?from=2026-11-10&to=2026-11-01', $this->apiHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['to']);

    $this->getJson('/api/v1/businesses/7/transactions?from=2026-11-01&to=2026-11-30', $this->apiHeaders())
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('responde 404 al confirmar un UPC inexistente', function (): void {
    $this->postJson('/api/v1/payments/aaaaaaaaaaaaaa/confirm', [], $this->apiHeaders())
        ->assertNotFound()
        ->assertJsonPath('message', 'No existe una transacción con UPC [aaaaaaaaaaaaaa].');
});
