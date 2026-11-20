<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\GestoPago\Data\PaymentRequest;
use Illuminate\Foundation\Http\FormRequest;

final class StorePaymentRequest extends FormRequest
{
    /**
     * Campos que se reenvían a Gesto Pago (nombres del contrato del proveedor).
     */
    private const array GATEWAY_FIELDS = ['idServicio', 'idProducto', 'referencia', 'telefono', 'montoPago'];

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            /** Id del negocio (farmacia) que cobra el servicio. */
            'businessId' => ['required', 'integer', 'min:1'],
            /** Servicio de Gesto Pago (ver GET /categories/{id}/products). */
            'idServicio' => ['required', 'integer', 'min:1'],
            'idProducto' => ['required', 'integer', 'min:1'],
            /** Número de referencia del recibo o cuenta (pagos de servicios). */
            'referencia' => ['nullable', 'string', 'max:64'],
            /** Teléfono a 10 dígitos (recargas). */
            'telefono' => ['nullable', 'string', 'regex:/^\d{10}$/'],
            /** Monto a pagar en MXN (servicios con monto abierto). */
            'montoPago' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    public function toPaymentRequest(): PaymentRequest
    {
        /** @var array<string, scalar|null> $fields */
        $fields = array_filter(
            $this->safe()->only(self::GATEWAY_FIELDS),
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );

        return new PaymentRequest($this->integer('businessId'), $fields);
    }
}
