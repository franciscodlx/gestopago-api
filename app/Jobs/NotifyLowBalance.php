<?php

namespace App\Jobs;

use App\Domain\GestoPago\Contracts\LowBalanceNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Avisa al core que el saldo con Gesto Pago bajó del umbral. Va en cola para no
 * retrasar ni hacer fallar el pago que lo detectó.
 */
final class NotifyLowBalance implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    /**
     * @param  array<string, mixed>  $gatewayResponse
     */
    public function __construct(public readonly array $gatewayResponse) {}

    public function handle(LowBalanceNotifier $notifier): void
    {
        $notifier->notify($this->gatewayResponse);
    }
}
