<?php

namespace App\Infrastructure\Persistence;

use App\Domain\GestoPago\Contracts\AccessTokenStore;

/**
 * JWT del proveedor en las funciones heredadas (gestopago.get_token_gestopago_avaible /
 * insert_token_gestopago). Útil mientras wsgestopago y este servicio convivan y deban
 * compartir el mismo token. La vigencia la decide la función de la BD.
 */
final class DatabaseAccessTokenStore implements AccessTokenStore
{
    private const string NO_TOKEN = 'FAIL';

    /** Tras un 401 se ignora el token de la BD hasta registrar uno nuevo. */
    private bool $invalidated = false;

    public function __construct(private readonly StoredFunctions $functions) {}

    public function get(): ?string
    {
        if ($this->invalidated) {
            return null;
        }

        $token = $this->functions->value('get_token_gestopago_avaible');

        return is_string($token) && $token !== '' && $token !== self::NO_TOKEN ? $token : null;
    }

    public function put(string $token): void
    {
        $this->functions->value('insert_token_gestopago', [$token]);
        $this->invalidated = false;
    }

    public function forget(): void
    {
        // La BD heredada no permite borrar: se ignora hasta que put() registre uno nuevo.
        $this->invalidated = true;
    }
}
