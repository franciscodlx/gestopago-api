<?php

namespace App\Application\GestoPago;

use App\Domain\GestoPago\Contracts\GestoPagoGateway;
use App\Domain\GestoPago\Data\GatewayResponse;

/**
 * Consulta con el proveedor si una referencia es válida para un servicio (p. ej. saldo de un recibo).
 */
final readonly class VerifyReference
{
    public function __construct(private GestoPagoGateway $gateway) {}

    /**
     * @param  array<string, scalar|null>  $fields  Al menos idServicio y referencia.
     */
    public function handle(array $fields): GatewayResponse
    {
        return $this->gateway->verifyReference(
            json_encode($fields, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
    }
}
