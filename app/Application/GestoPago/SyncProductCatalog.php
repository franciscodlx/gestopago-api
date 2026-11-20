<?php

namespace App\Application\GestoPago;

use App\Domain\GestoPago\Contracts\CatalogRepository;
use App\Domain\GestoPago\Contracts\GestoPagoGateway;
use App\Domain\GestoPago\Data\Product;
use App\Domain\GestoPago\Exceptions\GatewayUnavailableException;

/**
 * Descarga el catálogo de productos del proveedor y lo guarda localmente.
 */
final readonly class SyncProductCatalog
{
    public function __construct(
        private GestoPagoGateway $gateway,
        private CatalogRepository $catalog,
    ) {}

    /**
     * @return int Productos sincronizados.
     */
    public function handle(): int
    {
        $products = $this->gateway->products()->get('PRODUCTOS.producto');

        if (! is_array($products)) {
            throw GatewayUnavailableException::because('el catálogo no contiene PRODUCTOS.producto');
        }

        // Con un solo producto el XML se convierte en un nodo y no en una lista.
        if (array_key_exists('@attributes', $products)) {
            $products = [$products];
        }

        $synced = 0;

        foreach ($products as $node) {
            $attributes = is_array($node) ? ($node['@attributes'] ?? null) : null;

            if (! is_array($attributes)) {
                continue;
            }

            $this->catalog->saveProduct(Product::fromAttributes($attributes));
            $synced++;
        }

        return $synced;
    }
}
