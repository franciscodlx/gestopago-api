<?php

namespace App\Domain\GestoPago\Contracts;

use App\Domain\GestoPago\Data\Product;

interface CatalogRepository
{
    /** @return list<array<string, mixed>> */
    public function categories(): array;

    /** @return list<array<string, mixed>> */
    public function productsByCategory(int $categoryId): array;

    /** Inserta o actualiza el producto. */
    public function saveProduct(Product $product): void;
}
