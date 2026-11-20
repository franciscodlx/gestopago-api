<?php

use App\Domain\GestoPago\Data\NewTransaction;
use App\Domain\GestoPago\Data\Product;
use App\Domain\GestoPago\ValueObjects\Upc;
use App\Infrastructure\Persistence\DatabaseAccessTokenStore;
use App\Infrastructure\Persistence\PostgresApiKeyValidator;
use App\Infrastructure\Persistence\PostgresCatalogRepository;
use App\Infrastructure\Persistence\PostgresCommissionRepository;
use App\Infrastructure\Persistence\PostgresTransactionRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/*
 * Requiere PostgreSQL con database/sql/gestopago_schema.sql cargado:
 *   DB_CONNECTION=pgsql vendor/bin/pest --group=pgsql
 */

beforeEach(function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Requiere DB_CONNECTION=pgsql.');
    }

    DB::beginTransaction();
});

afterEach(function (): void {
    if (DB::getDriverName() === 'pgsql') {
        DB::rollBack();
    }
});

function newTransaction(Upc $upc, int $businessId = 7): NewTransaction
{
    return new NewTransaction($businessId, $upc, '5512345678', '1', '10', '20261119103015', "ref-'quote", '100.00', '{"upc":"'.$upc->value.'"}');
}

it('crea una transacción, registra el éxito y la recupera por UPC', function (): void {
    $repository = app(PostgresTransactionRepository::class);
    $upc = Upc::generate();

    $id = $repository->create(newTransaction($upc));
    $repository->recordSuccess($id, '987654', '{"ID_TX":"987654"}', "<R a='1'/>");

    expect($id)->toBeGreaterThan(0)
        ->and($repository->findRequestPayload($upc))->toBe('{"upc":"'.$upc->value.'"}')
        ->and($repository->findResponsePayload($upc))->toBe('{"ID_TX":"987654"}')
        ->and($repository->findResponsePayload(Upc::generate()))->toBeNull();

    $rows = $repository->listByBusiness(7, CarbonImmutable::today()->subDay(), CarbonImmutable::today());
    expect(collect($rows)->pluck('upc'))->toContain($upc->value);
});

it('no es vulnerable a inyección SQL en los parámetros', function (): void {
    $repository = app(PostgresTransactionRepository::class);
    $upc = Upc::generate();

    $id = $repository->create(new NewTransaction(7, $upc, "'); DROP TABLE gestopago.transactions; --", '1', '10', 'x', 'y', '1', '{}'));

    expect($id)->toBeGreaterThan(0)
        ->and(DB::table('gestopago.transactions')->where('upc', $upc->value)->value('telefono'))
        ->toBe("'); DROP TABLE gestopago.transactions; --");
});

it('guarda y actualiza productos del catálogo', function (): void {
    $catalog = app(PostgresCatalogRepository::class);
    $attributes = ['servicio' => 'Telcel', 'producto' => 'Recarga 100', 'idServicio' => '1', 'idProducto' => '9001',
        'idCatTipoServicio' => '1', 'tipoFront' => '1', 'hasDigitoVerificador' => 'false', 'precio' => '100', 'showAyuda' => 'false', 'tipoReferencia' => 'tel'];

    $catalog->saveProduct(Product::fromAttributes($attributes));
    $catalog->saveProduct(Product::fromAttributes([...$attributes, 'precio' => '150']));

    $product = collect($catalog->productsByCategory(1))->firstWhere('id_producto', 9001);
    expect($product['precio'])->toBe('150');
});

it('crea y actualiza comisiones', function (): void {
    $commissions = app(PostgresCommissionRepository::class);

    $created = $commissions->create(42, '3.5');
    $updated = $commissions->update((int) $created[0]['id'], '4');

    expect($updated[0]['commission'])->toBe('4')
        ->and($commissions->forBusiness(42))->toHaveCount(1);
});

it('valida API keys activas', function (): void {
    DB::table('gestopago.api_keys')->insert(['api_key' => 'integration-key']);
    $validator = app(PostgresApiKeyValidator::class);

    expect($validator->isValid('integration-key'))->toBeTrue()
        ->and($validator->isValid('otra'))->toBeFalse();
});

it('comparte el JWT del proveedor mediante las funciones heredadas', function (): void {
    $store = app(DatabaseAccessTokenStore::class);

    $store->put('jwt-integration');
    expect($store->get())->toBe('jwt-integration');

    $store->forget();
    expect($store->get())->toBeNull();
});
