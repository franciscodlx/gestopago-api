<?php

namespace App\Domain\GestoPago\Contracts;

interface LowBalanceNotifier
{
    /**
     * @param  array<string, mixed>  $gatewayResponse  Respuesta del proveedor que reportó el saldo.
     */
    public function notify(array $gatewayResponse): void;
}
