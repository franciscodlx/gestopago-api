<?php

use App\Domain\GestoPago\ValueObjects\Upc;

it('genera UPCs de 14 caracteres hexadecimales y únicos', function (): void {
    $upcs = collect(range(1, 50))->map(fn (): string => Upc::generate()->value);

    expect($upcs->unique())->toHaveCount(50)
        ->and($upcs->first())->toMatch('/^[a-f0-9]{14}$/');
});

it('acepta un UPC válido', function (): void {
    expect((string) Upc::fromString('0a1b2c3d4e5f60'))->toBe('0a1b2c3d4e5f60');
});

it('rechaza UPCs mal formados', function (string $value): void {
    Upc::fromString($value);
})->throws(InvalidArgumentException::class)->with(['', 'abc', 'ZZZZZZZZZZZZZZ', "0a1b2c3d4e5f60'; --"]);
