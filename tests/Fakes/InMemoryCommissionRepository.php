<?php

namespace Tests\Fakes;

use App\Domain\GestoPago\Contracts\CommissionRepository;

final class InMemoryCommissionRepository implements CommissionRepository
{
    /** @var array<int, array{id: int, business_id: int, commission: string}> */
    public array $rows = [];

    public function forBusiness(int $businessId): array
    {
        return array_values(array_filter($this->rows, fn (array $row): bool => $row['business_id'] === $businessId));
    }

    public function create(int $businessId, string $commission): array
    {
        $id = count($this->rows) + 1;
        $this->rows[$id] = ['id' => $id, 'business_id' => $businessId, 'commission' => $commission];

        return [$this->rows[$id]];
    }

    public function update(int $commissionBusinessId, string $commission): array
    {
        if (! isset($this->rows[$commissionBusinessId])) {
            return [];
        }

        $this->rows[$commissionBusinessId]['commission'] = $commission;

        return [$this->rows[$commissionBusinessId]];
    }
}
