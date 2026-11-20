<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\GestoPago\ConfirmPayment;
use App\Application\GestoPago\GetPaymentResponse;
use App\Application\GestoPago\SendPayment;
use App\Domain\GestoPago\ValueObjects\Upc;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Resources\PaymentResultResource;
use Illuminate\Http\JsonResponse;

/**
 * @tags Pagos
 */
final class PaymentController extends Controller
{
    /**
     * Enviar pago.
     *
     * Registra la transacción, la envía cifrada a Gesto Pago y devuelve la autorización.
     * Un rechazo del proveedor responde 422 con el `upc` para darle seguimiento.
     *
     * @status 201
     */
    public function store(StorePaymentRequest $request, SendPayment $sendPayment): JsonResponse
    {
        $result = $sendPayment->handle($request->toPaymentRequest());

        return (new PaymentResultResource($result))->response()->setStatusCode(201);
    }

    /**
     * Confirmar pago.
     *
     * Reenvía la solicitud original al proveedor para conocer el estado definitivo.
     */
    public function confirm(string $upc, ConfirmPayment $confirmPayment): JsonResponse
    {
        return response()->json(['data' => $confirmPayment->handle(Upc::fromString($upc))->data]);
    }

    /**
     * Respuesta registrada de un pago.
     */
    public function show(string $upc, GetPaymentResponse $getPaymentResponse): JsonResponse
    {
        return response()->json(['data' => $getPaymentResponse->handle(Upc::fromString($upc))]);
    }
}
