<?php

namespace App\Domain\GestoPago\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * Referencia interna única (UPC) con la que se rastrea una transacción
 * entre este servicio, la base de datos y Gesto Pago.
 */
final readonly class Upc implements Stringable
{
    public const int LENGTH = 14;

    private function __construct(public string $value) {}

    public static function generate(): self
    {
        return new self(substr(bin2hex(random_bytes(16)), 0, self::LENGTH));
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^[a-f0-9]{'.self::LENGTH.'}$/', $value) !== 1) {
            throw new InvalidArgumentException("UPC inválido: [{$value}].");
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
