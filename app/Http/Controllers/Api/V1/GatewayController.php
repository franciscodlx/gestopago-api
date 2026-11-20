<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\GestoPago\Contracts\GestoPagoGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Operaciones de consulta directa contra Gesto Pago.
 *
 * @tags Gesto Pago
 */
final class GatewayController extends Controller
{
    public function __construct(private readonly GestoPagoGateway $gateway) {}

    /**
     * Eco (prueba de conectividad con el proveedor).
     */
    public function echo(): JsonResponse
    {
        return response()->json(['data' => $this->gateway->echo()->data]);
    }

    /**
     * Datos del distribuidor/dispositivo autenticado (validateMe).
     */
    public function me(): JsonResponse
    {
        return response()->json(['data' => $this->gateway->validateMe()->data]);
    }

    /**
     * Saldo disponible con el proveedor.
     */
    public function balance(): JsonResponse
    {
        return response()->json(['data' => $this->gateway->balance()->data]);
    }
}
