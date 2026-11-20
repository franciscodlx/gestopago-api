<?php

namespace App\Application\GestoPago;

use App\Domain\GestoPago\Contracts\GestoPagoGateway;
use App\Domain\GestoPago\Contracts\TransactionRepository;
use App\Domain\GestoPago\Data\GatewayResponse;
use App\Domain\GestoPago\Exceptions\TransactionNotFoundException;
use App\Domain\GestoPago\ValueObjects\Upc;

/**
 * Reenvía a Gesto Pago la solicitud original de una transacción para confirmar su estado.
 */
final readonly class ConfirmPayment
{
    public function __construct(
        private GestoPagoGateway $gateway,
        private TransactionRepository $transactions,
    ) {}

    /**
     * @throws TransactionNotFoundException
     */
    public function handle(Upc $upc): GatewayResponse
    {
        $requestPayload = $this->transactions->findRequestPayload($upc)
            ?? throw TransactionNotFoundException::forUpc($upc);

        return $this->gateway->confirmTransaction($requestPayload);
    }
}
