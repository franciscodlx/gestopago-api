<?php

namespace App\Domain\GestoPago\Contracts;

interface PayloadCipher
{
    public function encrypt(string $plainText): string;

    public function decrypt(string $cipherText): string;
}
