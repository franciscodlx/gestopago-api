<?php

namespace App\Application\GestoPago;

use App\Domain\GestoPago\Contracts\TransactionRepository;
use App\Domain\GestoPago\Exceptions\TransactionNotFoundException;
use App\Domain\GestoPago\ValueObjects\Upc;

/**
 * Devuelve la respuesta que Gesto Pago dio a una transacción ya procesada.
 */
final readonly class GetPaymentResponse
{
    public function __construct(private TransactionRepository $transactions) {}

    /**
     * @return array<string, mixed>
     *
     * @throws TransactionNotFoundException
     */
    public function handle(Upc $upc): array
    {
        $responseJson = $this->transactions->findResponsePayload($upc)
            ?? throw TransactionNotFoundException::forUpc($upc);

        $decoded = json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }
}
