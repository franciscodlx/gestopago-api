<?php

namespace App\Domain\GestoPago\Contracts;

/**
 * Valida las API keys de los clientes que consumen este servicio.
 */
interface ApiKeyValidator
{
    public function isValid(string $apiKey): bool;
}
