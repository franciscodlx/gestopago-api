<?php

namespace App\Http\Middleware;

use App\Domain\GestoPago\Contracts\ApiKeyValidator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica al cliente con su API key.
 *
 * Header: X-Api-Key (v1) o "key" (heredado de wsgestopago). Con el parámetro
 * "legacy" responde igual que el servicio anterior (501 en texto plano).
 */
final readonly class AuthenticateApiKey
{
    public const string HEADER = 'X-Api-Key';

    private const string LEGACY_HEADER = 'key';

    public function __construct(private ApiKeyValidator $validator) {}

    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        $apiKey = (string) ($request->header(self::HEADER) ?? $request->header(self::LEGACY_HEADER) ?? '');

        if ($apiKey !== '' && $this->validator->isValid($apiKey)) {
            return $next($request);
        }

        if ($mode === 'legacy') {
            return response('APIKEY NOT FOUND OR NOT MATCH!', 501);
        }

        return response()->json(['message' => 'API key ausente o inválida.'], 401);
    }
}
