<?php

namespace App\Domain\GestoPago\Exceptions;

use RuntimeException;

/**
 * Base de los errores de negocio del módulo. Cada subclase define su estado HTTP.
 */
abstract class GestoPagoException extends RuntimeException
{
    abstract public function status(): int;

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [];
    }
}
