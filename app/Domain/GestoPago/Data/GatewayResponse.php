<?php

namespace App\Domain\GestoPago\Data;

/**
 * Respuesta de Gesto Pago: el XML original y su representación en arreglo.
 */
final readonly class GatewayResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $rawXml,
    ) {}

    public function get(string $path, mixed $default = null): mixed
    {
        return data_get($this->data, $path, $default);
    }

    public function code(): ?string
    {
        $code = $this->get('MENSAJE.CODIGO');

        return is_scalar($code) ? (string) $code : null;
    }

    public function message(): ?string
    {
        $message = $this->get('MENSAJE.TEXTO');

        return is_scalar($message) ? (string) $message : null;
    }

    /**
     * Texto CDATA del nodo <legend>, esté donde esté dentro del documento.
     */
    public function legend(): ?string
    {
        if (preg_match('/<legend>\s*<!\[CDATA\[(.*?)\]\]>\s*<\/legend>/s', $this->rawXml, $matches) === 1) {
            return $matches[1];
        }

        $legend = $this->get('legend');

        return is_string($legend) && $legend !== '' ? $legend : null;
    }
}
