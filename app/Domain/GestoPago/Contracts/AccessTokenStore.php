<?php

namespace App\Domain\GestoPago\Contracts;

/**
 * Almacén del JWT vigente del proveedor.
 */
interface AccessTokenStore
{
    public function get(): ?string;

    public function put(string $token): void;

    public function forget(): void;
}
