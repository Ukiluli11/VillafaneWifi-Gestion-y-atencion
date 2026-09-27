<?php

namespace App\Dominio;

use App\Enums\IntencionConversacion;
use App\Enums\OpcionMenuWhatsapp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Throwable;

class IAService
{
    /**
     * @return array{intencion: IntencionConversacion, confianza: float}
     */
    public function interpretarIntencion(string $texto, string $contextoConversacion = ''): array
    {
        $texto = trim($texto);
        if ($texto === '') {
            return $this->resultado(IntencionConversacion::Desconocida, 0.0);
        }

        $clave = (string) config('services.openai.api_key');
        $modelo = (string) config('services.openai.model');

        if ($clave !== '' && $modelo !== '') {
            try {
                return $this->clasificarConOpenAI($texto, $contextoConversacion, $clave, $modelo);
            } catch (Throwable $error) {
                Log::warning('Falló la clasificación de intención mediante IA; se utilizará el respaldo local.', [
                    'error' => $error->getMessage(),
                ]);
            }
        }

        return $this->clasificarLocalmente($texto);
    }

    /**
     * @return array{intencion: IntencionConversacion, confianza: float}
     */
    private function clasificarConOpenAI(
        string $texto,
        string $contextoConversacion,
        string $clave,
        string $modelo,
    ): array {
        $urlBase = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $timeout = (int) config('services.openai.timeout', 15);
        $intenciones = array_map(
            static fn (IntencionConversacion $intencion): string => $intencion->value,
            IntencionConversacion::cases(),
        );

        $respuesta = Http::withToken($clave)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout($timeout)
            ->post($urlBase.'/responses', [
                'model' => $modelo,
                'store' => false,
                'instructions' => implode(' ', [
                    'Clasificá la intención del cliente de Villafañe Wifi.',
                    'Usá consulta_cuenta para deuda, saldo, cuotas o vencimientos;',
                    'reclamo_soporte para fallas técnicas o reclamos;',
                    'envio_comprobante para pagos, transferencias o comprobantes;',
                    'atencion_humana cuando solicite una persona u operador;',
                    'y desconocida cuando no exista evidencia suficiente.',
                    'No inventes datos ni respondas la consulta del cliente.',
                ]),
                'input' => "Contexto reciente:\n{$contextoConversacion}\n\nMensaje a clasificar:\n{$texto}",
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'clasificacion_intencion',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'intencion' => ['type' => 'string', 'enum' => $intenciones],
                                'confianza' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                            ],
                            'required' => ['intencion', 'confianza'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
            ]);

        $respuesta->throw();
        $textoSalida = $this->extraerTextoSalida((array) $respuesta->json());

        try {
            $datos = json_decode($textoSalida, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException('La IA devolvió una clasificación inválida.', previous: $error);
        }

        $intencion = IntencionConversacion::tryFrom((string) ($datos['intencion'] ?? ''));
        $confianza = (float) ($datos['confianza'] ?? 0);
        if ($intencion === null || $confianza < 0 || $confianza > 1) {
            throw new RuntimeException('La IA devolvió valores fuera del contrato de clasificación.');
        }

        $minima = (float) config('services.openai.confianza_minima', 0.65);
        if ($confianza < $minima) {
            return $this->resultado(IntencionConversacion::Desconocida, $confianza);
        }

        return $this->resultado($intencion, $confianza);
    }

    /**
     * @return array{intencion: IntencionConversacion, confianza: float}
     */
    private function clasificarLocalmente(string $texto): array
    {
        $texto = mb_strtolower($texto);
        $opcion = OpcionMenuWhatsapp::desdeMensaje($texto);
        if ($opcion !== null) {
            return $this->resultado($opcion->intencion(), 1.0);
        }

        $reglas = [
            IntencionConversacion::AtencionHumana->value => '/\b(humano|persona|asesor|operador|empleado)\b/u',
            IntencionConversacion::EnvioComprobante->value => '/\b(comprobante|pagu[eé]|transferencia|dep[oó]sito|adjunto|pago realizado)\b/u',
            IntencionConversacion::ReclamoSoporte->value => '/\b(reclamo|soporte|sin internet|no funciona|corte|problema|lento|lentitud)\b/u',
            IntencionConversacion::ConsultaCuenta->value => '/\b(debo|deuda|saldo|vencimiento|cuota|estado de cuenta|al d[ií]a|mora)\b/u',
        ];

        foreach ($reglas as $valor => $patron) {
            if (preg_match($patron, $texto) === 1) {
                return $this->resultado(IntencionConversacion::from($valor), 0.85);
            }
        }

        return $this->resultado(IntencionConversacion::Desconocida, 0.0);
    }

    /** @param array<string, mixed> $respuesta */
    private function extraerTextoSalida(array $respuesta): string
    {
        foreach ((array) ($respuesta['output'] ?? []) as $salida) {
            if (! is_array($salida) || ($salida['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ((array) ($salida['content'] ?? []) as $contenido) {
                if (is_array($contenido) && ($contenido['type'] ?? null) === 'output_text') {
                    $texto = $contenido['text'] ?? null;
                    if (is_string($texto) && $texto !== '') {
                        return $texto;
                    }
                }
            }
        }

        throw new RuntimeException('La respuesta de IA no contiene texto de salida.');
    }

    /**
     * @return array{intencion: IntencionConversacion, confianza: float}
     */
    private function resultado(IntencionConversacion $intencion, float $confianza): array
    {
        return ['intencion' => $intencion, 'confianza' => $confianza];
    }
}
