<?php

use App\Domain\GestoPago\Contracts\LowBalanceNotifier;
use App\Jobs\NotifyLowBalance;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('envía la respuesta del proveedor al core', function (): void {
    Http::fake(['core.test/*' => Http::response()]);

    (new NotifyLowBalance(['SALDO' => '100']))->handle(app(LowBalanceNotifier::class));

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://core.test/gp/avisosaldo' && $request['SALDO'] === '100');
});

it('no llama a nada si no hay URL configurada', function (): void {
    config(['gestopago.low_balance.notify_url' => '']);
    Http::fake();

    (new NotifyLowBalance(['SALDO' => '100']))->handle(app(LowBalanceNotifier::class));

    Http::assertNothingSent();
});
