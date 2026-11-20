<?php

namespace App\Domain\GestoPago\Contracts;

use App\Domain\GestoPago\Data\GatewayResponse;

/**
 * Puerto hacia la API de Gesto Pago.
 *
 * @throws \App\Domain\GestoPago\Exceptions\GatewayUnavailableException
 */
interface GestoPagoGateway
{
    /** JWT vigente del proveedor (reutiliza el almacenado o pide uno nuevo). */
    public function accessToken(): string;

    public function echo(): GatewayResponse;

    public function validateMe(): GatewayResponse;

    public function balance(): GatewayResponse;

    public function products(): GatewayResponse;

    /** Envía la transacción; $payloadJson se cifra antes de salir. No se reintenta. */
    public function sendTransaction(string $payloadJson): GatewayResponse;

    public function confirmTransaction(string $payloadJson): GatewayResponse;

    public function verifyReference(string $payloadJson): GatewayResponse;
}
