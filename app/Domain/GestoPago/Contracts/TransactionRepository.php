<?php

namespace App\Domain\GestoPago\Contracts;

use App\Domain\GestoPago\Data\NewTransaction;
use App\Domain\GestoPago\ValueObjects\Upc;
use Carbon\CarbonImmutable;

interface TransactionRepository
{
    /** @return int Id interno de la transacción. */
    public function create(NewTransaction $transaction): int;

    public function recordSuccess(int $transactionId, string $gatewayTransactionId, string $responseJson, string $responseXml): void;

    /** JSON original que se envió al proveedor. */
    public function findRequestPayload(Upc $upc): ?string;

    /** JSON de la respuesta exitosa del proveedor. */
    public function findResponsePayload(Upc $upc): ?string;

    /**
     * @return list<array<string, mixed>>
     */
    public function listByBusiness(int $businessId, CarbonImmutable $from, CarbonImmutable $to): array;
}
