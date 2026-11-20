<?php

namespace App\Infrastructure\Persistence;

use App\Domain\GestoPago\Contracts\CatalogRepository;
use App\Domain\GestoPago\Data\Product;

final readonly class PostgresCatalogRepository implements CatalogRepository
{
    public function __construct(private StoredFunctions $functions) {}

    public function categories(): array
    {
        return $this->functions->rows('get_categories');
    }

    public function productsByCategory(int $categoryId): array
    {
        return $this->functions->rows('get_products_by_category', [$categoryId]);
    }

    public function saveProduct(Product $product): void
    {
        $this->functions->rows('insert_product', [
            $product->service,
            $product->product,
            $product->serviceId,
            $product->productId,
            $product->serviceTypeId,
            $product->frontType,
            $product->hasCheckDigit,
            $product->price,
            $product->showHelp,
            $product->referenceType,
        ]);
    }
}
