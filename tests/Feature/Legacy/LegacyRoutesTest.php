<?php

use App\Http\Middleware\UseLegacyDatabaseConnection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\GestoPagoFake;
use Tests\TestCase;

/*
 * Contratos de wsgestopago que los clientes actuales esperan sin cambios.
 */

beforeEach(function (): void {
    $this->useInMemoryPersistence();
    Bus::fake();
});

function legacyHeaders(): array
{
    return ['key' => TestCase::API_KEY, 'Accept' => 'application/json'];
}

it('responde 501 en texto plano sin API key', function (): void {
    $this->postJson('/api/PRODUCTION/main')
        ->assertStatus(501)
        ->assertSeeText('APIKEY NOT FOUND OR NOT MATCH!');
});

it('responde 404 para ambientes desconocidos', function (): void {
    $this->postJson('/api/STAGING/main', [], legacyHeaders())->assertNotFound();
});

it('selecciona la conexión según el ambiente y la restaura al terminar', function (string $environment, string $connection): void {
    $seen = null;
    $this->app['router']->post('api/{environment}/__probe', function () use (&$seen) {
        $seen = DB::getDefaultConnection();

        return response()->noContent();
    })->middleware(UseLegacyDatabaseConnection::class);

    $default = DB::getDefaultConnection();

    $this->post("/api/{$environment}/__probe")->assertNoContent();

    expect($seen)->toBe($connection)->and(DB::getDefaultConnection())->toBe($default);
})->with([
    ['PRODUCTION', 'pgsql'],
    ['DEVELOP', 'pgsql_develop'],
]);

it('main responde el string JSON "OK"', function (): void {
    expect($this->postJson('/api/DEVELOP/main', [], legacyHeaders())->assertOk()->getContent())->toBe('"OK"');
});

it('sendTx conserva el contrato heredado (201 + claves en mayúsculas)', function (): void {
    GestoPagoFake::fake(['send_transaction' => GestoPagoFake::successfulPaymentXml(withPin: true)]);

    $this->postJson('/api/PRODUCTION/sendTx', [
        'businessid' => 7, 'idServicio' => 1, 'idProducto' => 10, 'telefono' => '5512345678',
    ], legacyHeaders())
        ->assertCreated()
        ->assertJsonPath('ID_TX', '987654')
        ->assertJsonPath('NUM_AUTORIZACION', 'A1B2C3')
        ->assertJsonPath('PIN', '1234-5678')
        ->assertJsonPath('LEGENT', 'Conserve su ticket')
        ->assertJsonStructure(['ID_TX', 'NUM_AUTORIZACION', 'UPC', 'PIN', 'LEGENT']);
});

it('sendTx rechazado responde 500 con MESSAGE y UPC', function (): void {
    GestoPagoFake::fake(['send_transaction' => GestoPagoFake::rejectedPaymentXml()]);

    $this->postJson('/api/PRODUCTION/sendTx', ['businessid' => 7, 'idServicio' => 1, 'idProducto' => 10], legacyHeaders())
        ->assertStatus(500)
        ->assertJsonPath('MESSAGE', 'Referencia invalida')
        ->assertJsonStructure(['MESSAGE', 'UPC']);
});

it('responseTx y confirmTx funcionan con el upc en el cuerpo', function (): void {
    GestoPagoFake::fake([
        'send_transaction' => GestoPagoFake::successfulPaymentXml(),
        'confirm_transaction' => GestoPagoFake::simpleXml('01', 'Confirmada'),
    ]);

    $upc = $this->postJson('/api/PRODUCTION/sendTx', ['businessid' => 7, 'idServicio' => 1, 'idProducto' => 10], legacyHeaders())
        ->json('UPC');

    $this->postJson('/api/PRODUCTION/responseTx', ['upc' => $upc], legacyHeaders())->assertOk()->assertJsonPath('ID_TX', '987654');
    $this->postJson('/api/PRODUCTION/confirmTx', ['upc' => $upc], legacyHeaders())->assertOk()->assertJsonPath('MENSAJE.TEXTO', 'Confirmada');
    $this->postJson('/api/PRODUCTION/responseTx', ['upc' => 'inexistente'], legacyHeaders())->assertStatus(500);
});

it('getListProducts responde texto plano', function (): void {
    GestoPagoFake::fake(['products' => GestoPagoFake::productsXml()]);

    $this->post('/api/PRODUCTION/getListProducts', [], legacyHeaders())
        ->assertOk()
        ->assertSeeText('Carga de productos realizada');

    expect($this->catalog->products)->toHaveCount(2);
});

it('mantiene catálogo, comisiones y transacciones con los nombres de campos heredados', function (): void {
    $this->getJson('/api/PRODUCTION/categories', legacyHeaders())->assertOk()->assertJsonPath('0.name', 'Recargas');
    $this->postJson('/api/PRODUCTION/productsByCategory', ['categoryId' => 1], legacyHeaders())->assertOk();

    $this->putJson('/api/PRODUCTION/commissionBusiness', ['businessid' => 7, 'commissionin' => '3'], legacyHeaders())->assertOk();
    $this->patchJson('/api/PRODUCTION/commissionBusiness', ['commissionbusinessid' => 1, 'commissionin' => '4'], legacyHeaders())
        ->assertOk()
        ->assertJsonPath('0.commission', '4');
    $this->postJson('/api/PRODUCTION/commissionBusiness', ['businessid' => 7], legacyHeaders())->assertOk()->assertJsonCount(1);

    $this->postJson('/api/PRODUCTION/getTransaccionsByClient', [
        'bussinessid' => 7, 'fechaInicio' => '2026-11-01', 'fechaFin' => '2026-11-30',
    ], legacyHeaders())->assertOk();
});

it('verifyReference reenvía los campos recibidos', function (): void {
    GestoPagoFake::fake(['verify_reference' => GestoPagoFake::simpleXml()]);

    $this->postJson('/api/DEVELOP/verifyReference', ['idServicio' => 2, 'referencia' => '123'], legacyHeaders())
        ->assertOk()
        ->assertJsonPath('MENSAJE.CODIGO', '01');
});

it('sendEcho, validateMe y getBalance devuelven el XML convertido', function (string $route, string $endpoint): void {
    GestoPagoFake::fake([$endpoint => GestoPagoFake::simpleXml('01', $route)]);

    $this->postJson("/api/PRODUCTION/{$route}", [], legacyHeaders())->assertOk()->assertJsonPath('MENSAJE.TEXTO', $route);
})->with([
    ['sendEcho', 'echo'],
    ['validateMe', 'validate_me'],
    ['getBalance', 'balance'],
]);

it('encryptData y getJWT responden 201 en sandbox', function (): void {
    GestoPagoFake::fake();

    $this->postJson('/api/DEVELOP/encryptData', ['a' => 1], legacyHeaders())->assertCreated();
    expect($this->postJson('/api/DEVELOP/getJWT', [], legacyHeaders())->assertCreated()->json())->toBe(GestoPagoFake::TOKEN);
});

it('getListProducts responde 500 en texto si el proveedor falla', function (): void {
    GestoPagoFake::fake(['products' => Http::response('', 503)]);

    $this->post('/api/PRODUCTION/getListProducts', [], legacyHeaders())
        ->assertStatus(500)
        ->assertSeeText('Ocurrio un error en el servicio.');
});

it('sendTx responde el mensaje genérico si el proveedor no está disponible', function (): void {
    GestoPagoFake::fake(['send_transaction' => Http::response('', 503)]);

    expect($this->postJson('/api/PRODUCTION/sendTx', ['businessid' => 7, 'idServicio' => 1, 'idProducto' => 10], legacyHeaders())
        ->assertStatus(500)
        ->json())->toBe('Ocurrio un problema, intenta de nuevo');
});
