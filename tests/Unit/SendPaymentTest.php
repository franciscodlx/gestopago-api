<?php

use App\Application\GestoPago\SendPayment;
use App\Domain\GestoPago\Data\PaymentRequest;
use App\Domain\GestoPago\Exceptions\GatewayUnavailableException;
use App\Domain\GestoPago\Exceptions\PaymentRejectedException;
use App\Jobs\NotifyLowBalance;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\Support\GestoPagoFake;

beforeEach(function (): void {
    $this->useInMemoryPersistence();
    Bus::fake();
    $this->travelTo(now()->setTimezone('America/Mexico_City')->setDate(2026, 11, 19)->setTime(10, 30, 15));
});

function paymentRequest(): PaymentRequest
{
    return new PaymentRequest(7, ['idServicio' => 1, 'idProducto' => 10, 'telefono' => '5512345678', 'montoPago' => '100.00']);
}

it('registra, envía cifrado y devuelve la autorización', function (): void {
    GestoPagoFake::fake(['send_transaction' => GestoPagoFake::successfulPaymentXml(withPin: true)]);

    $result = app(SendPayment::class)->handle(paymentRequest());

    expect($result->transactionId)->toBe('987654')
        ->and($result->authorizationNumber)->toBe('A1B2C3')
        ->and($result->pin)->toBe('1234-5678')
        ->and($result->legend)->toBe('Conserve su ticket');

    $record = $this->transactions->records[1];
    expect($record['transaction']->businessId)->toBe(7)
        ->and($record['transaction']->upc->value)->toBe($result->upc->value)
        ->and($record['idTx'])->toBe('987654')
        ->and($record['responseXml'])->toContain('<ID_TX>987654</ID_TX>');

    Http::assertSent(function (Request $request) use ($result): bool {
        if (! str_ends_with($request->url(), '/sendTx')) {
            return false;
        }

        expect($request->header('Authorization'))->toBe(['Bearer '.GestoPagoFake::TOKEN])
            ->and(GestoPagoFake::decryptSigned($request))->toBe([
                'idServicio' => 1,
                'idProducto' => 10,
                'telefono' => '5512345678',
                'montoPago' => '100.00',
                'unidad' => 'assert',
                'upc' => $result->upc->value,
                'horaLocal' => '20261119103015',
            ]);

        return true;
    });
});

it('persiste la transacción antes de llamar al proveedor aunque éste la rechace', function (): void {
    GestoPagoFake::fake(['send_transaction' => GestoPagoFake::rejectedPaymentXml()]);

    expect(fn () => app(SendPayment::class)->handle(paymentRequest()))
        ->toThrow(PaymentRejectedException::class, 'Referencia invalida');

    expect($this->transactions->records)->toHaveCount(1)
        ->and($this->transactions->records[1]['idTx'])->toBeNull();
});

it('encola el aviso cuando el saldo queda bajo el umbral', function (): void {
    GestoPagoFake::fake(['send_transaction' => GestoPagoFake::successfulPaymentXml(balance: '4999.99')]);

    app(SendPayment::class)->handle(paymentRequest());

    Bus::assertDispatched(NotifyLowBalance::class, fn (NotifyLowBalance $job): bool => $job->gatewayResponse['SALDO'] === '4999.99');
});

it('no avisa cuando el saldo es suficiente', function (): void {
    GestoPagoFake::fake(['send_transaction' => GestoPagoFake::successfulPaymentXml(balance: '5000')]);

    app(SendPayment::class)->handle(paymentRequest());

    Bus::assertNotDispatched(NotifyLowBalance::class);
});

it('no reintenta el envío ante un error del proveedor', function (): void {
    GestoPagoFake::fake(['send_transaction' => Http::response('boom', 500)]);

    expect(fn () => app(SendPayment::class)->handle(paymentRequest()))->toThrow(GatewayUnavailableException::class);

    Http::assertSentCount(2); // token + un solo envío
});
