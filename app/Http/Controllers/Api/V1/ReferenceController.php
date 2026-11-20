<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\GestoPago\VerifyReference;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VerifyReferenceRequest;
use Illuminate\Http\JsonResponse;

/**
 * @tags Pagos
 */
final class ReferenceController extends Controller
{
    /**
     * Verificar referencia.
     *
     * Valida una referencia (recibo, contrato) con el proveedor antes de cobrar.
     */
    public function __invoke(VerifyReferenceRequest $request, VerifyReference $verifyReference): JsonResponse
    {
        return response()->json(['data' => $verifyReference->handle($request->validated())->data]);
    }
}
