<?php

namespace App\Domain\GestoPago\Data;

/**
 * Solicitud de pago de un servicio (recarga, pago de referencia, PIN, etc.).
 */
final readonly class PaymentRequest
{
    /**
     * @param  array<string, scalar|null>  $fields  Campos que se envían tal cual a Gesto Pago
     *                                              (idServicio, idProducto, referencia, telefono, montoPago, ...).
     */
    public function __construct(
        public int $businessId,
        public array $fields,
    ) {}
}
