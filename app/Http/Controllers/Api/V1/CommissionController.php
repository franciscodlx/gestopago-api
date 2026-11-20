<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\GestoPago\Contracts\CommissionRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CommissionRequest;
use Illuminate\Http\JsonResponse;

/**
 * @tags Comisiones
 */
final class CommissionController extends Controller
{
    public function __construct(private readonly CommissionRepository $commissions) {}

    /**
     * Comisiones de un negocio.
     */
    public function index(int $businessId): JsonResponse
    {
        return response()->json(['data' => $this->commissions->forBusiness($businessId)]);
    }

    /**
     * Registrar comisión de un negocio.
     *
     * @status 201
     */
    public function store(int $businessId, CommissionRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->commissions->create($businessId, $request->commission())], 201);
    }

    /**
     * Actualizar comisión.
     */
    public function update(int $commissionId, CommissionRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->commissions->update($commissionId, $request->commission())]);
    }
}
