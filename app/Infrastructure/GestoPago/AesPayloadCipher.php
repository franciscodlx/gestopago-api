<?php

namespace App\Infrastructure\GestoPago;

use App\Domain\GestoPago\Contracts\PayloadCipher;
use App\Domain\GestoPago\Exceptions\MisconfiguredGatewayException;
use RuntimeException;

/**
 * AES-256-CBC con llave e IV fijos entregados por Gesto Pago (contrato del proveedor).
 * Entrada/salida en base64, igual que el campo "signed" que espera la API.
 */
final readonly class AesPayloadCipher implements PayloadCipher
{
    private const string CIPHER = 'aes-256-cbc';

    public function __construct(private GestoPagoSettings $settings) {}

    public function encrypt(string $plainText): string
    {
        [$key, $iv] = $this->material();

        $encrypted = openssl_encrypt($plainText, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('No fue posible cifrar el payload para Gesto Pago.');
        }

        return base64_encode($encrypted);
    }

    public function decrypt(string $cipherText): string
    {
        [$key, $iv] = $this->material();

        $raw = base64_decode($cipherText, true);
        $decrypted = $raw === false ? false : openssl_decrypt($raw, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('No fue posible descifrar el payload de Gesto Pago.');
        }

        return $decrypted;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function material(): array
    {
        $key = base64_decode($this->settings->encryptionKey(), true);
        $iv = base64_decode($this->settings->encryptionIv(), true);

        if ($key === false || strlen($key) !== 32) {
            throw MisconfiguredGatewayException::missing('encryption.key (32 bytes en base64)');
        }

        if ($iv === false || strlen($iv) !== openssl_cipher_iv_length(self::CIPHER)) {
            throw MisconfiguredGatewayException::missing('encryption.iv (16 bytes en base64)');
        }

        return [$key, $iv];
    }
}
