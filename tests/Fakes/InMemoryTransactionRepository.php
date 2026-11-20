<?php

namespace Tests\Fakes;

use App\Domain\GestoPago\Contracts\TransactionRepository;
use App\Domain\GestoPago\Data\NewTransaction;
use App\Domain\GestoPago\ValueObjects\Upc;
use Carbon\CarbonImmutable;

final class InMemoryTransactionRepository implements TransactionRepository
{
    /** @var array<int, array{transaction: NewTransaction, idTx: ?string, responseJson: ?string, responseXml: ?string}> */
    public array $records = [];

    public function create(NewTransaction $transaction): int
    {
        $id = count($this->records) + 1;
        $this->records[$id] = ['transaction' => $transaction, 'idTx' => null, 'responseJson' => null, 'responseXml' => null];

        return $id;
    }

    public function recordSuccess(int $transactionId, string $gatewayTransactionId, string $responseJson, string $responseXml): void
    {
        $this->records[$transactionId]['idTx'] = $gatewayTransactionId;
        $this->records[$transactionId]['responseJson'] = $responseJson;
        $this->records[$transactionId]['responseXml'] = $responseXml;
    }

    public function findRequestPayload(Upc $upc): ?string
    {
        return $this->find($upc)['transaction']->requestJson ?? null;
    }

    public function findResponsePayload(Upc $upc): ?string
    {
        return $this->find($upc)['responseJson'] ?? null;
    }

    public function listByBusiness(int $businessId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = [];

        foreach ($this->records as $id => $record) {
            if ($record['transaction']->businessId === $businessId) {
                $rows[] = ['id' => $id, 'upc' => $record['transaction']->upc->value, 'id_tx' => $record['idTx']];
            }
        }

        return $rows;
    }

    /**
     * @return array{transaction: NewTransaction, idTx: ?string, responseJson: ?string, responseXml: ?string}|null
     */
    private function find(Upc $upc): ?array
    {
        foreach ($this->records as $record) {
            if ($record['transaction']->upc->value === $upc->value) {
                return $record;
            }
        }

        return null;
    }
}
