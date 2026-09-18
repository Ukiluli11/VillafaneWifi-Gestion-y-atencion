<?php

namespace App\Infraestructura\InteligenciaArtificial;

use App\Contratos\ClasificadorIntencion;
use App\Dominio\ResultadoClasificacionIntencion;
use App\Enums\IntencionWhatsapp;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Clasifica mensajes mediante la salida estructurada de Gemini. */
class ClasificadorIntencionGemini implements ClasificadorIntencion
{
    public function __construct(
        private readonly ClasificadorIntencionLocal $respaldo,
    ) {}

    public function clasificar(string $mensaje): ResultadoClasificacionIntencion
    {
        $clave = (string) config('services.gemini.clave_api');
        if ($clave === '' || trim($mensaje) === '') {
            return $this->respaldo->clasificar($mensaje);
        }

        try {
            $modelo = (string) config('services.gemini.modelo', 'gemini-3.5-flash-lite');
            $urlBase = rtrim((string) config('services.gemini.url_base'), '/');
            $respuesta = Http::baseUrl($urlBase)
                ->withHeaders(['x-goog-api-key' => $clave])
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(8)
                ->post("/v1beta/models/{$modelo}:generateContent", [
                    'contents' => [[
                        'parts' => [[
                            'text' => $this->crearPrompt($mensaje),
                        ]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0,
                        'maxOutputTokens' => 100,
                        'responseFormat' => [
                            'text' => [
                                'mimeType' => 'application/json',
                                'schema' => $this->esquemaRespuesta(),
                            ],
                        ],
                    ],
                ])
                ->throw();

            $contenido = Arr::get($respuesta->json(), 'candidates.0.content.parts.0.text');
            $datos = is_string($contenido) ? json_decode($contenido, true) : null;
            $intencion = IntencionWhatsapp::tryFrom((string) Arr::get($datos, 'intencion'));
            $confianza = Arr::get($datos, 'confianza');
            if ($intencion === null || ! is_numeric($confianza)) {
                return $this->respaldo->clasificar($mensaje);
            }

            return new ResultadoClasificacionIntencion(
                $intencion,
                max(0.0, min(1.0, (float) $confianza)),
                'gemini',
            );
        } catch (Throwable $error) {
            report($error);

            return $this->respaldo->clasificar($mensaje);
        }
    }

    private function crearPrompt(string $mensaje): string
    {
        return <<<'PROMPT'
Clasificá el mensaje de un cliente de un proveedor de Internet.
Elegí una sola intención:
- consultar_estado_cuenta: deuda, cuotas, saldo o vencimiento.
- informar_pago: pagó, transfirió o quiere enviar un comprobante.
- registrar_reclamo: problemas técnicos, cortes, lentitud o reclamos administrativos.
- solicitar_atencion_humana: pide hablar con una persona o empleado.
- no_reconocida: no coincide claramente con las anteriores.

No respondas al cliente ni inventes información. Clasificá solamente este mensaje:
PROMPT
            ."\n\n{$mensaje}";
    }

    /** @return array<string, mixed> */
    private function esquemaRespuesta(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'intencion' => [
                    'type' => 'string',
                    'enum' => array_column(IntencionWhatsapp::cases(), 'value'),
                ],
                'confianza' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
            ],
            'required' => ['intencion', 'confianza'],
            'additionalProperties' => false,
        ];
    }
}
