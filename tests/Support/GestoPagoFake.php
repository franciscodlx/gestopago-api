<?php

namespace Tests\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Simula la API de Gesto Pago con Http::fake usando las rutas de phpunit.xml.
 */
final class GestoPagoFake
{
    public const string BASE_URL = 'https://gestopago.test';

    public const string TOKEN = 'jwt-de-prueba';

    /**
     * @param  array<string, string|\GuzzleHttp\Promise\PromiseInterface>  $responses  endpoint => respuesta (XML o fake)
     */
    public static function fake(array $responses = []): void
    {
        $paths = [
            'token' => '/token',
            'echo' => '/echo',
            'validate_me' => '/validateMe',
            'balance' => '/balance',
            'products' => '/products',
            'send_transaction' => '/sendTx',
            'confirm_transaction' => '/confirmTx',
            'verify_reference' => '/verifyReference',
        ];

        $fakes = [self::BASE_URL.'/token*' => Http::response(['token' => self::TOKEN])];

        foreach ($responses as $endpoint => $response) {
            $fakes[self::BASE_URL.$paths[$endpoint]] = is_string($response)
                ? Http::response($response, 200, ['Content-Type' => 'application/xml'])
                : $response;
        }

        Http::fake($fakes);
    }

    public static function successfulPaymentXml(string $balance = '15000.00', bool $withPin = false): string
    {
        $pin = $withPin ? '<PIN>1234-5678</PIN>' : '';

        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <RESPUESTA>
                <MENSAJE><CODIGO>01</CODIGO><TEXTO>Transaccion exitosa</TEXTO>{$pin}</MENSAJE>
                <ID_TX>987654</ID_TX>
                <NUM_AUTORIZACION>A1B2C3</NUM_AUTORIZACION>
                <SALDO>{$balance}</SALDO>
                <legend><![CDATA[Conserve su ticket]]></legend>
            </RESPUESTA>
            XML;
    }

    public static function rejectedPaymentXml(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <RESPUESTA>
                <MENSAJE><CODIGO>14</CODIGO><TEXTO>Referencia invalida</TEXTO></MENSAJE>
            </RESPUESTA>
            XML;
    }

    public static function productsXml(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <RESPUESTA>
                <PRODUCTOS>
                    <producto servicio="Telcel" producto="Recarga 100" idServicio="1" idProducto="10" idCatTipoServicio="1" tipoFront="1" hasDigitoVerificador="false" precio="100.00" showAyuda="false" tipoReferencia="telefono"/>
                    <producto servicio="CFE" producto="Pago CFE" idServicio="2" idProducto="20" idCatTipoServicio="2" tipoFront="2" hasDigitoVerificador="true" precio="0" showAyuda="true" tipoReferencia="referencia"/>
                </PRODUCTOS>
            </RESPUESTA>
            XML;
    }

    public static function simpleXml(string $code = '01', string $text = 'OK'): string
    {
        return "<RESPUESTA><MENSAJE><CODIGO>{$code}</CODIGO><TEXTO>{$text}</TEXTO></MENSAJE></RESPUESTA>";
    }

    /**
     * Payload JSON descifrado del campo "signed" de una petición multipart.
     *
     * @return array<string, mixed>
     */
    public static function decryptSigned(Request $request): array
    {
        $signed = collect($request->data())->firstWhere('name', 'signed')['contents'] ?? '';
        $plain = app(\App\Domain\GestoPago\Contracts\PayloadCipher::class)->decrypt((string) $signed);

        return json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
    }
}
