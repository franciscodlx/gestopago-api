<?php

namespace App\Http\Controllers\Legacy;

use App\Application\GestoPago\ConfirmPayment;
use App\Application\GestoPago\GetPaymentResponse;
use App\Application\GestoPago\SendPayment;
use App\Application\GestoPago\SyncProductCatalog;
use App\Application\GestoPago\VerifyReference;
use App\Domain\GestoPago\Contracts\CatalogRepository;
use App\Domain\GestoPago\Contracts\CommissionRepository;
use App\Domain\GestoPago\Contracts\GestoPagoGateway;
use App\Domain\GestoPago\Contracts\PayloadCipher;
use App\Domain\GestoPago\Contracts\TransactionRepository;
use App\Domain\GestoPago\Data\PaymentRequest;
use App\Domain\GestoPago\Exceptions\GestoPagoException;
use App\Domain\GestoPago\Exceptions\PaymentRejectedException;
use App\Domain\GestoPago\ValueObjects\Upc;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;
use Throwable;

/**
 * Adaptador de compatibilidad con wsgestopago (Lumen 8).
 *
 * Conserva rutas, nombres de campos, códigos de estado y forma de las respuestas
 * para que los clientes actuales migren sin cambios. Toda la lógica vive en los
 * casos de uso compartidos con /api/v1. Deprecado: nuevos clientes usan /api/v1.
 */
final class LegacyGestoPagoController extends Controller
{
    private const string GENERIC_ERROR = 'Ocurrio un problema, intenta de nuevo';

    public function main(): JsonResponse
    {
        return response()->json('OK');
    }

    public function encryptData(Request $request, PayloadCipher $cipher): JsonResponse
    {
        abort_unless(config('gestopago.diagnostics_enabled') && ! app()->isProduction(), 404);

        return response()->json($cipher->encrypt((string) json_encode($request->all())), 201);
    }

    public function getJwt(GestoPagoGateway $gateway): JsonResponse
    {
        abort_unless(config('gestopago.diagnostics_enabled') && ! app()->isProduction(), 404);

        return response()->json($gateway->accessToken(), 201);
    }

    public function sendEcho(GestoPagoGateway $gateway): JsonResponse
    {
        return response()->json($gateway->echo()->data);
    }

    public function validateMe(GestoPagoGateway $gateway): JsonResponse
    {
        return response()->json($gateway->validateMe()->data);
    }

    public function getBalance(GestoPagoGateway $gateway): JsonResponse
    {
        return response()->json($gateway->balance()->data);
    }

    public function getListProducts(SyncProductCatalog $syncProductCatalog): Response
    {
        try {
            $syncProductCatalog->handle();
        } catch (Throwable $exception) {
            report($exception);

            return response('Ocurrio un error en el servicio.', 500);
        }

        return response('Carga de productos realizada', 200);
    }

    public function sendTx(Request $request, SendPayment $sendPayment): JsonResponse
    {
        $request->validate([
            'businessid' => ['required', 'integer'],
            'idServicio' => ['required'],
            'idProducto' => ['required'],
        ]);

        /** @var array<string, scalar|null> $fields */
        $fields = array_filter($request->except('businessid'), is_scalar(...));

        try {
            $result = $sendPayment->handle(new PaymentRequest($request->integer('businessid'), $fields));
        } catch (PaymentRejectedException $exception) {
            return response()->json(['MESSAGE' => $exception->getMessage(), 'UPC' => $exception->upc->value], 500);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(self::GENERIC_ERROR, 500);
        }

        return response()->json(array_filter([
            'ID_TX' => $result->transactionId,
            'NUM_AUTORIZACION' => $result->authorizationNumber,
            'UPC' => $result->upc->value,
            'PIN' => $result->pin,
            'LEGENT' => $result->legend,
        ], static fn (?string $value): bool => $value !== null), 201);
    }

    public function confirmTx(Request $request, ConfirmPayment $confirmPayment): JsonResponse
    {
        $request->validate(['upc' => ['required', 'string']]);

        try {
            return response()->json($confirmPayment->handle(Upc::fromString($request->string('upc')->toString()))->data);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function responseTx(Request $request, GetPaymentResponse $getPaymentResponse): JsonResponse
    {
        $request->validate(['upc' => ['required', 'string']]);

        try {
            return response()->json($getPaymentResponse->handle(Upc::fromString($request->string('upc')->toString())));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'Ocurrio un problema inesperado, intenta de nuevo');
        }
    }

    public function verifyReference(Request $request, VerifyReference $verifyReference): JsonResponse
    {
        $request->validate([
            'idServicio' => ['required'],
            'referencia' => ['required'],
        ]);

        /** @var array<string, scalar|null> $fields */
        $fields = array_filter($request->all(), is_scalar(...));

        return response()->json($verifyReference->handle($fields)->data);
    }

    public function getTransactionsByClient(Request $request, TransactionRepository $transactions): JsonResponse
    {
        // "bussinessid" (sic) es el nombre que envían los clientes actuales.
        $validated = $request->validate([
            'bussinessid' => ['required', 'integer'],
            'fechaInicio' => ['required', 'date'],
            'fechaFin' => ['required', 'date'],
        ]);

        return response()->json($transactions->listByBusiness(
            (int) $validated['bussinessid'],
            CarbonImmutable::parse($validated['fechaInicio']),
            CarbonImmutable::parse($validated['fechaFin']),
        ));
    }

    public function categories(CatalogRepository $catalog): JsonResponse
    {
        return response()->json($catalog->categories());
    }

    public function productsByCategory(Request $request, CatalogRepository $catalog): JsonResponse
    {
        $request->validate(['categoryId' => ['required', 'integer']]);

        return response()->json($catalog->productsByCategory($request->integer('categoryId')));
    }

    public function getCommission(Request $request, CommissionRepository $commissions): JsonResponse
    {
        $request->validate(['businessid' => ['required', 'integer']]);

        return response()->json($commissions->forBusiness($request->integer('businessid')));
    }

    public function insertCommission(Request $request, CommissionRepository $commissions): JsonResponse
    {
        $request->validate(['businessid' => ['required', 'integer'], 'commissionin' => ['required']]);

        return response()->json($commissions->create($request->integer('businessid'), (string) $request->input('commissionin')));
    }

    public function updateCommission(Request $request, CommissionRepository $commissions): JsonResponse
    {
        $request->validate(['commissionbusinessid' => ['required', 'integer'], 'commissionin' => ['required']]);

        return response()->json($commissions->update($request->integer('commissionbusinessid'), (string) $request->input('commissionin')));
    }

    private function failure(Throwable $exception, string $message = self::GENERIC_ERROR): JsonResponse
    {
        if (! $exception instanceof GestoPagoException && ! $exception instanceof InvalidArgumentException) {
            report($exception);
        }

        return response()->json($message, 500);
    }
}
