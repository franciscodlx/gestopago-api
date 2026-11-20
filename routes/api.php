<?php

use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CommissionController;
use App\Http\Controllers\Api\V1\DiagnosticsController;
use App\Http\Controllers\Api\V1\GatewayController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReferenceController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Legacy\LegacyGestoPagoController;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnsureDiagnosticsEnabled;
use App\Http\Middleware\UseLegacyDatabaseConnection;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')
    ->name('v1.')
    ->middleware([AuthenticateApiKey::class, 'throttle:api'])
    ->group(function (): void {
        Route::controller(GatewayController::class)->prefix('gateway')->name('gateway.')->group(function (): void {
            Route::get('echo', 'echo')->name('echo');
            Route::get('me', 'me')->name('me');
            Route::get('balance', 'balance')->name('balance');
        });

        Route::controller(PaymentController::class)->prefix('payments')->name('payments.')->group(function (): void {
            Route::post('/', 'store')->name('store');
            Route::get('{upc}', 'show')->name('show')->where('upc', '[a-f0-9]{14}');
            Route::post('{upc}/confirm', 'confirm')->name('confirm')->where('upc', '[a-f0-9]{14}');
        });

        Route::post('references/verify', ReferenceController::class)->name('references.verify');

        Route::controller(CatalogController::class)->name('catalog.')->group(function (): void {
            Route::get('categories', 'categories')->name('categories');
            Route::get('categories/{categoryId}/products', 'products')->name('products')->whereNumber('categoryId');
            Route::post('catalog/sync', 'sync')->name('sync');
        });

        Route::controller(CommissionController::class)->name('commissions.')->group(function (): void {
            Route::get('businesses/{businessId}/commissions', 'index')->name('index')->whereNumber('businessId');
            Route::post('businesses/{businessId}/commissions', 'store')->name('store')->whereNumber('businessId');
            Route::patch('commissions/{commissionId}', 'update')->name('update')->whereNumber('commissionId');
        });

        Route::get('businesses/{businessId}/transactions', [TransactionController::class, 'index'])
            ->name('transactions.index')
            ->whereNumber('businessId');

        Route::controller(DiagnosticsController::class)
            ->prefix('diagnostics')
            ->name('diagnostics.')
            ->middleware(EnsureDiagnosticsEnabled::class)
            ->group(function (): void {
                Route::post('encrypt', 'encrypt')->name('encrypt');
                Route::get('token', 'token')->name('token');
            });
    });

/*
|--------------------------------------------------------------------------
| Compatibilidad wsgestopago: /api/{PRODUCTION|DEVELOP}/<operación>
|--------------------------------------------------------------------------
|
| Deprecado. Mismos contratos que el servicio Lumen anterior.
|
*/

if (config('gestopago.legacy.enabled')) {
    Route::prefix('{environment}')
        ->where(['environment' => 'PRODUCTION|DEVELOP'])
        ->name('legacy.')
        ->middleware([UseLegacyDatabaseConnection::class, AuthenticateApiKey::class.':legacy', 'throttle:api'])
        ->controller(LegacyGestoPagoController::class)
        ->group(function (): void {
            Route::post('main', 'main')->name('main');
            Route::post('encryptData', 'encryptData')->name('encrypt-data');
            Route::post('getJWT', 'getJwt')->name('get-jwt');
            Route::post('sendEcho', 'sendEcho')->name('send-echo');
            Route::post('validateMe', 'validateMe')->name('validate-me');
            Route::post('getBalance', 'getBalance')->name('get-balance');
            Route::post('getListProducts', 'getListProducts')->name('get-list-products');
            Route::post('sendTx', 'sendTx')->name('send-tx');
            Route::post('confirmTx', 'confirmTx')->name('confirm-tx');
            Route::post('responseTx', 'responseTx')->name('response-tx');
            Route::post('verifyReference', 'verifyReference')->name('verify-reference');
            Route::post('getTransaccionsByClient', 'getTransactionsByClient')->name('transactions-by-client');
            Route::get('categories', 'categories')->name('categories');
            Route::post('productsByCategory', 'productsByCategory')->name('products-by-category');
            Route::post('commissionBusiness', 'getCommission')->name('commission.show');
            Route::put('commissionBusiness', 'insertCommission')->name('commission.store');
            Route::patch('commissionBusiness', 'updateCommission')->name('commission.update');
        });
}
