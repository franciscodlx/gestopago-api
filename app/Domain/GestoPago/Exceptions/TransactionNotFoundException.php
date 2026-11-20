<?php

namespace App\Domain\GestoPago\Exceptions;

use App\Domain\GestoPago\ValueObjects\Upc;

final class TransactionNotFoundException extends GestoPagoException
{
    public static function forUpc(Upc $upc): self
    {
        return new self("No existe una transacción con UPC [{$upc->value}].");
    }

    public function status(): int
    {
        return 404;
    }
}
