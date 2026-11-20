<?php

namespace App\Http\Resources;

use App\Domain\GestoPago\Data\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PaymentResult $resource
 */
final class PaymentResultResource extends JsonResource
{
    /**
     * @return array{transactionId: string, authorizationNumber: string, upc: string, pin: string|null, legend: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            /** ID_TX de Gesto Pago. */
            'transactionId' => $this->resource->transactionId,
            /** NUM_AUTORIZACION que se imprime en el ticket. */
            'authorizationNumber' => $this->resource->authorizationNumber,
            /** Referencia interna para confirmar o consultar la transacción. */
            'upc' => $this->resource->upc->value,
            /** PIN (tarjetas de regalo / servicios digitales). */
            'pin' => $this->resource->pin,
            /** Leyenda legal que debe mostrarse al cliente. */
            'legend' => $this->resource->legend,
        ];
    }
}
