<?php

namespace App\Domain\GestoPago\Data;

/**
 * Producto del catálogo de Gesto Pago (atributos del nodo <producto>).
 */
final readonly class Product
{
    public function __construct(
        public string $service,
        public string $product,
        public int $serviceId,
        public int $productId,
        public int $serviceTypeId,
        public int $frontType,
        public string $hasCheckDigit,
        public string $price,
        public string $showHelp,
        public string $referenceType,
    ) {}

    /**
     * @param  array<string, scalar|null>  $attributes
     */
    public static function fromAttributes(array $attributes): self
    {
        return new self(
            service: (string) ($attributes['servicio'] ?? ''),
            product: (string) ($attributes['producto'] ?? ''),
            serviceId: (int) ($attributes['idServicio'] ?? 0),
            productId: (int) ($attributes['idProducto'] ?? 0),
            serviceTypeId: (int) ($attributes['idCatTipoServicio'] ?? 0),
            frontType: (int) ($attributes['tipoFront'] ?? 0),
            hasCheckDigit: (string) ($attributes['hasDigitoVerificador'] ?? ''),
            price: (string) ($attributes['precio'] ?? ''),
            showHelp: (string) ($attributes['showAyuda'] ?? ''),
            referenceType: (string) ($attributes['tipoReferencia'] ?? ''),
        );
    }
}
