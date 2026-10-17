<?php

namespace App\Dominio;

use App\Enums\EstadoComprobante;
use App\Enums\OrigenComprobante;
use App\Enums\TipoMensaje;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Conversacion;
use App\Models\Mensaje;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ServicioRecepcionComprobantes
{
    private const FORMATOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    public function __construct(private readonly WhatsAppService $whatsAppService) {}

    public function procesar(Conversacion $conversacion, Mensaje $mensaje, Cliente $cliente): ?string
    {
        $esperando = $conversacion->paso_actual === 'esperando_comprobante';
        $esAdjunto = in_array($mensaje->tipo, [TipoMensaje::Imagen, TipoMensaje::Documento], true);

        if (! $esperando && ! $esAdjunto) {
            return null;
        }

        if (! $esAdjunto || ! str_starts_with((string) $mensaje->archivo_adjunto, 'meta://')) {
            return 'Adjuntá el comprobante como imagen JPG o PNG, o como archivo PDF.';
        }

        $existente = Comprobante::query()->where('id_mensaje', $mensaje->id_mensaje)->first();
        if ($existente !== null) {
            $conversacion->update(['paso_actual' => null]);
            $conversacion->cerrar();

            return "El comprobante #{$existente->id_comprobante} ya había sido recibido.";
        }

        try {
            $archivo = $this->whatsAppService->descargarArchivo((string) $mensaje->archivo_adjunto);
            $extension = self::FORMATOS[$archivo['mime_type']] ?? null;
            if ($extension === null) {
                return 'El formato no es válido. Enviá una imagen JPG o PNG, o un archivo PDF.';
            }

            $hash = hash('sha256', $archivo['contenido']);
            $duplicado = Comprobante::query()->where('hash_archivo', $hash)->first();
            if ($duplicado !== null) {
                $conversacion->update(['paso_actual' => null]);
                $conversacion->cerrar();

                return "El archivo coincide con el comprobante #{$duplicado->id_comprobante}, que ya está registrado.";
            }

            $disco = (string) config('services.whatsapp.media_disk', 'local');
            $ruta = sprintf('comprobantes/%d/%s.%s', $cliente->id_cliente, Str::uuid(), $extension);
            if (! Storage::disk($disco)->put($ruta, $archivo['contenido'])) {
                throw new \RuntimeException('No se pudo guardar el archivo del comprobante.');
            }

            try {
                $comprobante = DB::transaction(function () use ($conversacion, $mensaje, $cliente, $archivo, $ruta, $hash): Comprobante {
                    $registro = Comprobante::create([
                        'id_cliente' => $cliente->id_cliente,
                        'id_conversacion' => $conversacion->id_conversacion,
                        'id_mensaje' => $mensaje->id_mensaje,
                        'fecha_recepcion' => now(),
                        'nombre_original' => $mensaje->contenido,
                        'mime_type' => $archivo['mime_type'],
                        'tamanio_bytes' => $archivo['tamanio_bytes'],
                        'hash_archivo' => $hash,
                        'ruta_archivo' => $ruta,
                        'estado_validacion' => EstadoComprobante::Pendiente,
                        'origen' => OrigenComprobante::Whatsapp,
                    ]);
                    $mensaje->update(['archivo_adjunto' => $ruta]);
                    $conversacion->update(['paso_actual' => null]);
                    $conversacion->cerrar();

                    return $registro;
                });
            } catch (Throwable $error) {
                Storage::disk($disco)->delete($ruta);
                throw $error;
            }

            return "Recibimos y resguardamos tu comprobante #{$comprobante->id_comprobante}. Quedará disponible para su posterior validación.";
        } catch (Throwable $error) {
            Log::warning('No se pudo procesar un comprobante recibido por WhatsApp.', [
                'id_conversacion' => $conversacion->id_conversacion,
                'id_mensaje' => $mensaje->id_mensaje,
                'error' => $error->getMessage(),
            ]);

            return 'No pudimos guardar el archivo. Verificá que sea JPG, PNG o PDF y que no supere el tamaño permitido.';
        }
    }
}
