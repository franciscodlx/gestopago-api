<?php

namespace App\Infrastructure\Persistence;

use App\Domain\GestoPago\Contracts\TransactionRepository;
use App\Domain\GestoPago\Data\NewTransaction;
use App\Domain\GestoPago\ValueObjects\Upc;
use Carbon\CarbonImmutable;
use RuntimeException;

final readonly class PostgresTransactionRepository implements TransactionRepository
{
    public function __construct(private StoredFunctions $functions) {}

    public function create(NewTransaction $transaction): int
    {
        // El nombre "insert_trasaction" (sic) es el de la función existente en la BD.
        $id = $this->functions->value('insert_trasaction', [
            $transaction->phone,
            $transaction->serviceId,
            $transaction->productId,
            $transaction->localTime,
            $transaction->reference,
            $transaction->amount,
            $transaction->upc->value,
            $transaction->requestJson,
            $transaction->businessId,
        ]);

        if (! is_numeric($id)) {
            throw new RuntimeException('No se pudo registrar la transacción antes de enviarla a Gesto Pago.');
        }

        return (int) $id;
    }

    public function recordSuccess(int $transactionId, string $gatewayTransactionId, string $responseJson, string $responseXml): void
    {
        $this->functions->rows('updated_transaction', [
            $responseJson,
            $gatewayTransactionId,
            $transactionId,
            $responseXml,
        ]);
    }

    public function findRequestPayload(Upc $upc): ?string
    {
        return $this->stringOrNull($this->functions->value('get_request_transaction_by_upc', [$upc->value]));
    }

    public function findResponsePayload(Upc $upc): ?string
    {
        return $this->stringOrNull($this->functions->value('get_response_transaction_by_upc', [$upc->value]));
    }

    public function listByBusiness(int $businessId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->functions->rows('get_transaction_by_business', [
            $businessId,
            $from->toDateString(),
            $to->toDateString(),
        ]);
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
