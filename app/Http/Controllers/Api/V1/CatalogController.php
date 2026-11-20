<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\GestoPago\SyncProductCatalog;
use App\Domain\GestoPago\Contracts\CatalogRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @tags Catálogo
 */
final class CatalogController extends Controller
{
    public function __construct(private readonly CatalogRepository $catalog) {}

    /**
     * Categorías de servicios.
     */
    public function categories(): JsonResponse
    {
        return response()->json(['data' => $this->catalog->categories()]);
    }

    /**
     * Productos de una categoría.
     */
    public function products(int $categoryId): JsonResponse
    {
        return response()->json(['data' => $this->catalog->productsByCategory($categoryId)]);
    }

    /**
     * Sincronizar catálogo con Gesto Pago.
     *
     * También disponible como `php artisan gestopago:sync-products` (programado a diario).
     */
    public function sync(SyncProductCatalog $syncProductCatalog): JsonResponse
    {
        return response()->json(['data' => ['synced' => $syncProductCatalog->handle()]]);
    }
}
