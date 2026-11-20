<?php

namespace App\Infrastructure\GestoPago;

use App\Domain\GestoPago\Exceptions\MisconfiguredGatewayException;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Acceso tipado a config/gestopago.php. Falla en el momento de uso (no al arrancar)
 * para que endpoints que no dependen del proveedor sigan funcionando.
 */
final readonly class GestoPagoSettings
{
    public function __construct(private Config $config) {}

    public function baseUrl(): string
    {
        return $this->required('base_url');
    }

    public function endpoint(string $name): string
    {
        return '/'.ltrim($this->required("endpoints.{$name}"), '/');
    }

    /**
     * @return array{idDistribuidor: string, codigoDispositivo: string, password: string}
     */
    public function credentials(): array
    {
        return [
            'idDistribuidor' => $this->required('credentials.distributor_id'),
            'codigoDispositivo' => $this->required('credentials.device_code'),
            'password' => $this->required('credentials.password'),
        ];
    }

    public function encryptionKey(): string
    {
        return $this->required('encryption.key');
    }

    public function encryptionIv(): string
    {
        return $this->required('encryption.iv');
    }

    public function connectTimeout(): int
    {
        return (int) $this->config->get('gestopago.http.connect_timeout', 10);
    }

    public function timeout(): int
    {
        return (int) $this->config->get('gestopago.http.timeout', 60);
    }

    public function retries(): int
    {
        return max(0, (int) $this->config->get('gestopago.http.retries', 2));
    }

    public function retryDelayMs(): int
    {
        return (int) $this->config->get('gestopago.http.retry_delay_ms', 300);
    }

    public function tokenTtlSeconds(): int
    {
        return (int) $this->config->get('gestopago.token.ttl_seconds', 23 * 3600);
    }

    private function required(string $key): string
    {
        $value = $this->config->get("gestopago.{$key}");

        if (! is_scalar($value) || (string) $value === '') {
            throw MisconfiguredGatewayException::missing($key);
        }

        return (string) $value;
    }
}
