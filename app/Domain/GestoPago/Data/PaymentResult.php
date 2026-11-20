<?php

namespace App\Domain\GestoPago\Data;

use App\Domain\GestoPago\ValueObjects\Upc;

final readonly class PaymentResult
{
    public function __construct(
        public string $transactionId,
        public string $authorizationNumber,
        public Upc $upc,
        public ?string $pin = null,
        public ?string $legend = null,
        public ?float $balance = null,
    ) {}
}
