<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\ConnectionResolverInterface;
use InvalidArgumentException;

/**
 * Invoca las funciones del esquema "gestopago" de PostgreSQL con parámetros enlazados.
 *
 * - Sustituye la interpolación de strings de wsgestopago (inyección SQL).
 * - Usa la conexión por defecto en cada llamada: las rutas heredadas
 *   /api/{PRODUCTION|DEVELOP} la cambian por petición.
 * - Los valores se envían como texto (tipo "unknown" para PostgreSQL) para que el
 *   servidor resuelva la sobrecarga con la firma declarada de cada función; PDO
 *   enviaría los enteros como int8 y "integer" no se resolvería.
 */
final readonly class StoredFunctions
{
    private const string SCHEMA = 'gestopago';

    public function __construct(private ConnectionResolverInterface $db) {}

    /**
     * Filas completas: SELECT * FROM gestopago.fn(?, ...).
     *
     * @param  list<scalar|null>  $arguments
     * @return list<array<string, mixed>>
     */
    public function rows(string $function, array $arguments = []): array
    {
        $rows = $this->db->connection()->select(
            sprintf('SELECT * FROM %s.%s(%s)', self::SCHEMA, $this->name($function), $this->placeholders($arguments)),
            $this->bindings($arguments),
        );

        return array_values(array_map(static fn (object $row): array => (array) $row, $rows));
    }

    /**
     * Valor escalar de una función que devuelve un solo valor.
     *
     * @param  list<scalar|null>  $arguments
     */
    public function value(string $function, array $arguments = []): mixed
    {
        $row = $this->db->connection()->selectOne(
            sprintf('SELECT %s.%s(%s) AS result', self::SCHEMA, $this->name($function), $this->placeholders($arguments)),
            $this->bindings($arguments),
        );

        return $row?->result;
    }

    private function name(string $function): string
    {
        if (preg_match('/^[a-z_][a-z0-9_]*$/', $function) !== 1) {
            throw new InvalidArgumentException("Nombre de función inválido: [{$function}].");
        }

        return $function;
    }

    /**
     * @param  list<scalar|null>  $arguments
     */
    private function placeholders(array $arguments): string
    {
        return implode(', ', array_fill(0, count($arguments), '?'));
    }

    /**
     * @param  list<scalar|null>  $arguments
     * @return list<string|null>
     */
    private function bindings(array $arguments): array
    {
        return array_map(
            static fn (mixed $value): ?string => match (true) {
                $value === null => null,
                is_bool($value) => $value ? 'true' : 'false',
                default => (string) $value,
            },
            $arguments,
        );
    }
}
