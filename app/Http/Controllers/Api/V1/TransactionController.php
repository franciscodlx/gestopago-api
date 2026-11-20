<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\GestoPago\Contracts\TransactionRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListTransactionsRequest;
use Illuminate\Http\JsonResponse;

/**
 * @tags Pagos
 */
final class TransactionController extends Controller
{
    /**
     * Transacciones de un negocio en un rango de fechas.
     */
    public function index(int $businessId, ListTransactionsRequest $request, TransactionRepository $transactions): JsonResponse
    {
        return response()->json([
            'data' => $transactions->listByBusiness($businessId, $request->from(), $request->to()),
        ]);
    }
}
