<?php

namespace App\Infrastructure\GestoPago;

use App\Domain\GestoPago\Data\GatewayResponse;
use App\Domain\GestoPago\Exceptions\GatewayUnavailableException;
use SimpleXMLElement;

/**
 * Convierte el XML de Gesto Pago en arreglo con la misma forma que producía
 * json_encode(SimpleXMLElement) en wsgestopago (atributos bajo "@attributes").
 */
final class XmlResponseParser
{
    public function parse(string $xml): GatewayResponse
    {
        if (trim($xml) === '') {
            throw GatewayUnavailableException::because('respuesta vacía');
        }

        $previous = libxml_use_internal_errors(true);

        try {
            // LIBXML_NONET: nunca resolver recursos de red declarados en el documento.
            $element = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($element === false) {
            throw GatewayUnavailableException::because('respuesta XML inválida');
        }

        $data = json_decode((string) json_encode($element), true);

        return new GatewayResponse(is_array($data) ? $data : [], $xml);
    }
}
