<?php

namespace App\Application\GestoPago;

use App\Domain\GestoPago\Contracts\GestoPagoGateway;
use App\Domain\GestoPago\Contracts\TransactionRepository;
use App\Domain\GestoPago\Data\GatewayResponse;
use App\Domain\GestoPago\Data\NewTransaction;
use App\Domain\GestoPago\Data\PaymentRequest;
use App\Domain\GestoPago\Data\PaymentResult;
use App\Domain\GestoPago\Exceptions\GatewayUnavailableException;
use App\Domain\GestoPago\Exceptions\PaymentRejectedException;
use App\Domain\GestoPago\ValueObjects\Upc;
use App\Jobs\NotifyLowBalance;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository as Config;
use Throwable;

/**
 * Envía una transacción de pago a Gesto Pago.
 *
 * 1. Genera el UPC y completa el payload (unidad, upc, horaLocal).
 * 2. Persiste la solicitud ANTES de llamar al proveedor (trazabilidad).
 * 3. Envía el payload cifrado; código 01 = éxito.
 * 4. Guarda la respuesta y, si el saldo quedó bajo el umbral, encola el aviso.
 */
final readonly class SendPayment
{
    private const string SUCCESS_CODE = '01';

    public function __construct(
        private GestoPagoGateway $gateway,
        private TransactionRepository $transactions,
        private Config $config,
    ) {}

    /**
     * @throws PaymentRejectedException
     * @throws GatewayUnavailableException
     */
    public function handle(PaymentRequest $request): PaymentResult
    {
        $upc = Upc::generate();
        $localTime = CarbonImmutable::now((string) $this->config->get('gestopago.transaction.timezone'))->format('YmdHis');
        $fields = $request->fields;

        $payloadJson = json_encode([
            ...$fields,
            'unidad' => (string) $this->config->get('gestopago.transaction.unit'),
            'upc' => $upc->value,
            'horaLocal' => $localTime,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $transactionId = $this->transactions->create(new NewTransaction(
            businessId: $request->businessId,
            upc: $upc,
            phone: (string) ($fields['telefono'] ?? ''),
            serviceId: (string) ($fields['idServicio'] ?? ''),
            productId: (string) ($fields['idProducto'] ?? ''),
            localTime: $localTime,
            reference: (string) ($fields['referencia'] ?? ''),
            amount: (string) ($fields['montoPago'] ?? '0.00'),
            requestJson: $payloadJson,
        ));

        $response = $this->gateway->sendTransaction($payloadJson);

        if ($response->code() !== self::SUCCESS_CODE) {
            throw new PaymentRejectedException(
                $upc,
                $response->code(),
                $response->message() ?? 'Gesto Pago rechazó la transacción.',
            );
        }

        $gatewayTransactionId = $this->scalar($response, 'ID_TX');

        if ($gatewayTransactionId === null) {
            throw GatewayUnavailableException::because('respuesta exitosa sin ID_TX');
        }

        $this->transactions->recordSuccess(
            $transactionId,
            $gatewayTransactionId,
            json_encode($response->data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            $response->rawXml,
        );

        $balance = $this->balanceOf($response);
        $this->notifyIfBalanceIsLow($balance, $response);

        return new PaymentResult(
            transactionId: $gatewayTransactionId,
            authorizationNumber: $this->scalar($response, 'NUM_AUTORIZACION') ?? '',
            upc: $upc,
            pin: $this->scalar($response, 'MENSAJE.PIN'),
            legend: $response->legend(),
            balance: $balance,
        );
    }

    private function balanceOf(GatewayResponse $response): ?float
    {
        $balance = $response->get('SALDO');

        return is_numeric($balance) ? (float) $balance : null;
    }

    private function notifyIfBalanceIsLow(?float $balance, GatewayResponse $response): void
    {
        $threshold = (float) $this->config->get('gestopago.low_balance.threshold');

        if ($balance === null || $balance >= $threshold) {
            return;
        }

        try {
            NotifyLowBalance::dispatch($response->data);
        } catch (Throwable $exception) {
            // El pago ya se cobró: un fallo al encolar el aviso no debe convertirlo en error.
            report($exception);
        }
    }

    private function scalar(GatewayResponse $response, string $path): ?string
    {
        $value = $response->get($path);

        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }
}
