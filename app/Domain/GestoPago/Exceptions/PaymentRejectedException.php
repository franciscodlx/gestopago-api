<?php

namespace App\Domain\GestoPago\Exceptions;

use App\Domain\GestoPago\ValueObjects\Upc;

/**
 * El proveedor procesó la solicitud pero la rechazó (código distinto de 01).
 */
final class PaymentRejectedException extends GestoPagoException
{
    public function __construct(
        public readonly Upc $upc,
        public readonly ?string $gatewayCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function context(): array
    {
        return ['upc' => $this->upc->value, 'code' => $this->gatewayCode];
    }
}
