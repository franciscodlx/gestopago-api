<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\GestoPago\Contracts\GestoPagoGateway;
use App\Domain\GestoPago\Contracts\PayloadCipher;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EncryptPayloadRequest;
use Illuminate\Http\JsonResponse;

/**
 * Herramientas de soporte para integrar con el sandbox. Deshabilitadas en producción.
 *
 * @tags Diagnóstico
 */
final class DiagnosticsController extends Controller
{
    /**
     * Cifrar payload.
     *
     * Devuelve el valor `signed` que se enviaría a Gesto Pago para el objeto dado.
     */
    public function encrypt(EncryptPayloadRequest $request, PayloadCipher $cipher): JsonResponse
    {
        $json = json_encode($request->validated('payload'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        return response()->json(['data' => ['signed' => $cipher->encrypt($json)]]);
    }

    /**
     * JWT vigente del proveedor.
     */
    public function token(GestoPagoGateway $gateway): JsonResponse
    {
        return response()->json(['data' => ['token' => $gateway->accessToken()]]);
    }
}
