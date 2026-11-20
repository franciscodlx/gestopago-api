<?php

namespace App\Domain\GestoPago\Exceptions;

use Throwable;

/**
 * Gesto Pago no respondió, respondió con error HTTP o con un cuerpo ilegible.
 */
final class GatewayUnavailableException extends GestoPagoException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self("Gesto Pago no disponible: {$reason}", 0, $previous);
    }

    public function status(): int
    {
        return 502;
    }
}
