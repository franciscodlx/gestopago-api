<?php

namespace App\Infrastructure\Persistence;

use App\Domain\GestoPago\Contracts\CommissionRepository;

final readonly class PostgresCommissionRepository implements CommissionRepository
{
    public function __construct(private StoredFunctions $functions) {}

    public function forBusiness(int $businessId): array
    {
        return $this->functions->rows('get_commission_by_business', [$businessId]);
    }

    public function create(int $businessId, string $commission): array
    {
        return $this->functions->rows('insert_commission_business', [$businessId, $commission]);
    }

    public function update(int $commissionBusinessId, string $commission): array
    {
        return $this->functions->rows('updated_commission_business', [$commissionBusinessId, $commission]);
    }
}
