<?php

namespace App\Infrastructure\GestoPago;

use App\Domain\GestoPago\Contracts\LowBalanceNotifier;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;

/**
 * Avisa al core (endpoint gp/avisosaldo) para que envíe el correo de saldo insuficiente.
 */
final readonly class HttpLowBalanceNotifier implements LowBalanceNotifier
{
    public function __construct(
        private HttpFactory $http,
        private Config $config,
    ) {}

    public function notify(array $gatewayResponse): void
    {
        $url = (string) $this->config->get('gestopago.low_balance.notify_url');

        if ($url === '') {
            Log::warning('Saldo de Gesto Pago bajo el umbral y sin GESTOPAGO_LOW_BALANCE_NOTIFY_URL configurada.', [
                'saldo' => $gatewayResponse['SALDO'] ?? null,
            ]);

            return;
        }

        $this->http->asJson()->timeout(15)->post($url, $gatewayResponse)->throw();
    }
}
