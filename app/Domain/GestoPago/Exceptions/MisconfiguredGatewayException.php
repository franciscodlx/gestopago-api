<?php

namespace App\Domain\GestoPago\Exceptions;

/**
 * Falta configuración obligatoria (URL, credenciales o claves de cifrado).
 */
final class MisconfiguredGatewayException extends GestoPagoException
{
    public static function missing(string $key): self
    {
        return new self("Configuración de Gesto Pago incompleta: falta [gestopago.{$key}].");
    }

    public function status(): int
    {
        return 500;
    }
}
