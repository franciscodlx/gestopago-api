<?php

use App\Domain\GestoPago\Exceptions\MisconfiguredGatewayException;
use App\Infrastructure\GestoPago\AesPayloadCipher;

it('cifra igual que wsgestopago (AES-256-CBC, RAW, base64)', function (): void {
    $plain = '{"idServicio":1,"referencia":"123"}';
    $legacy = base64_encode(openssl_encrypt(
        $plain,
        'AES-256-CBC',
        base64_decode((string) config('gestopago.encryption.key')),
        OPENSSL_RAW_DATA,
        base64_decode((string) config('gestopago.encryption.iv')),
    ));

    expect(app(AesPayloadCipher::class)->encrypt($plain))->toBe($legacy);
});

it('descifra lo que cifra', function (): void {
    $cipher = app(AesPayloadCipher::class);

    expect($cipher->decrypt($cipher->encrypt('hola ñandú')))->toBe('hola ñandú');
});

it('falla si la llave no mide 32 bytes', function (): void {
    config(['gestopago.encryption.key' => base64_encode('corta')]);

    app(AesPayloadCipher::class)->encrypt('x');
})->throws(MisconfiguredGatewayException::class);

it('falla si falta la configuración', function (): void {
    config(['gestopago.encryption.iv' => null]);

    app(AesPayloadCipher::class)->encrypt('x');
})->throws(MisconfiguredGatewayException::class);
