<?php

namespace App\Infrastructure\GestoPago;

use App\Domain\GestoPago\Contracts\AccessTokenStore;
use App\Domain\GestoPago\Contracts\GestoPagoGateway;
use App\Domain\GestoPago\Contracts\PayloadCipher;
use App\Domain\GestoPago\Data\GatewayResponse;
use App\Domain\GestoPago\Exceptions\GatewayUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Adaptador HTTP de la API de Gesto Pago.
 *
 * - Autenticación: JWT Bearer obtenido con las credenciales del distribuidor.
 * - Operaciones con payload: multipart con el campo "signed" (JSON cifrado AES-256-CBC).
 * - Respuestas: XML.
 * - Un 401 invalida el token y se reintenta una vez con uno nuevo.
 */
final readonly class HttpGestoPagoGateway implements GestoPagoGateway
{
    public function __construct(
        private HttpFactory $http,
        private GestoPagoSettings $settings,
        private AccessTokenStore $tokens,
        private PayloadCipher $cipher,
        private XmlResponseParser $parser,
    ) {}

    public function accessToken(): string
    {
        $cached = $this->tokens->get();

        if ($cached !== null && $cached !== '') {
            return $cached;
        }

        $response = $this->send(
            fn (): Response => $this->client(idempotent: true)
                ->withQueryParameters($this->settings->credentials())
                ->post($this->settings->endpoint('token')),
        );

        $token = $response->json('token');

        if (! is_string($token) || $token === '') {
            throw GatewayUnavailableException::because('el endpoint de token no devolvió "token"');
        }

        $this->tokens->put($token);

        return $token;
    }

    public function echo(): GatewayResponse
    {
        return $this->call('echo', idempotent: true);
    }

    public function validateMe(): GatewayResponse
    {
        return $this->call('validate_me', idempotent: true);
    }

    public function balance(): GatewayResponse
    {
        return $this->call('balance', idempotent: true);
    }

    public function products(): GatewayResponse
    {
        return $this->call('products', idempotent: true);
    }

    public function sendTransaction(string $payloadJson): GatewayResponse
    {
        return $this->call('send_transaction', $payloadJson, idempotent: false);
    }

    public function confirmTransaction(string $payloadJson): GatewayResponse
    {
        return $this->call('confirm_transaction', $payloadJson, idempotent: true);
    }

    public function verifyReference(string $payloadJson): GatewayResponse
    {
        return $this->call('verify_reference', $payloadJson, idempotent: true);
    }

    private function call(string $endpoint, ?string $payloadJson = null, bool $idempotent = true): GatewayResponse
    {
        $path = $this->settings->endpoint($endpoint);
        $signed = $payloadJson === null ? null : $this->cipher->encrypt($payloadJson);

        $request = function () use ($path, $signed, $idempotent): Response {
            $client = $this->client($idempotent)->withToken($this->accessToken());

            return $signed === null
                ? $client->post($path)
                : $client->asMultipart()->post($path, [['name' => 'signed', 'contents' => $signed]]);
        };

        $response = $this->send($request, retryOnUnauthorized: true);

        return $this->parser->parse($response->body());
    }

    /**
     * @param  callable(): Response  $request
     */
    private function send(callable $request, bool $retryOnUnauthorized = false): Response
    {
        try {
            $response = $request();

            if ($retryOnUnauthorized && $response->status() === 401) {
                $this->tokens->forget();
                $response = $request();
            }

            return $response->throw();
        } catch (ConnectionException $exception) {
            throw GatewayUnavailableException::because('sin conexión', $exception);
        } catch (RequestException $exception) {
            throw GatewayUnavailableException::because("HTTP {$exception->response->status()}", $exception);
        }
    }

    private function client(bool $idempotent): PendingRequest
    {
        $client = $this->http
            ->baseUrl($this->settings->baseUrl())
            ->connectTimeout($this->settings->connectTimeout())
            ->timeout($this->settings->timeout())
            ->withHeaders(['Accept' => 'application/xml, application/json']);

        if ($idempotent && $this->settings->retries() > 0) {
            // Solo errores de conexión: un 4xx/5xx se reporta tal cual.
            $client->retry(
                $this->settings->retries(),
                $this->settings->retryDelayMs(),
                fn (Throwable $exception): bool => $exception instanceof ConnectionException,
                throw: false,
            );
        }

        return $client;
    }
}
