<?php

namespace App\Domain\GestoPago\Contracts;

interface CommissionRepository
{
    /** @return list<array<string, mixed>> */
    public function forBusiness(int $businessId): array;

    /** @return list<array<string, mixed>> */
    public function create(int $businessId, string $commission): array;

    /** @return list<array<string, mixed>> */
    public function update(int $commissionBusinessId, string $commission): array;
}
