<?php

namespace App\Domain\GestoPago\Data;

use App\Domain\GestoPago\ValueObjects\Upc;

/**
 * Registro que se persiste ANTES de enviar la transacción al proveedor.
 */
final readonly class NewTransaction
{
    public function __construct(
        public int $businessId,
        public Upc $upc,
        public string $phone,
        public string $serviceId,
        public string $productId,
        public string $localTime,
        public string $reference,
        public string $amount,
        public string $requestJson,
    ) {}
}
