<?php

namespace Tests\Fakes;

use App\Domain\GestoPago\Contracts\CatalogRepository;
use App\Domain\GestoPago\Data\Product;

final class InMemoryCatalogRepository implements CatalogRepository
{
    /** @var array<int, Product> */
    public array $products = [];

    /** @var list<array<string, mixed>> */
    public array $categoryRows = [['id' => 1, 'name' => 'Recargas']];

    public function categories(): array
    {
        return $this->categoryRows;
    }

    public function productsByCategory(int $categoryId): array
    {
        $rows = [];

        foreach ($this->products as $product) {
            if ($product->serviceTypeId === $categoryId) {
                $rows[] = ['id_producto' => $product->productId, 'producto' => $product->product];
            }
        }

        return $rows;
    }

    public function saveProduct(Product $product): void
    {
        $this->products[$product->productId] = $product;
    }
}
