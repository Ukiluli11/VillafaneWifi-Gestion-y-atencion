<?php

namespace App\Infraestructura\Whatsapp;

use App\Contratos\DescargadorArchivosWhatsapp;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Descarga de forma privada las imágenes y los PDF informados por Meta. */
class DescargadorArchivosMetaWhatsapp implements DescargadorArchivosWhatsapp
{
    /** @var array<string, string> */
    private const EXTENSIONES_PERMITIDAS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    public function descargar(string $identificadorArchivo): string
    {
        $configuracion = $this->configuracion();
        $metadatos = $this->solicitud($configuracion['token'])
            ->get("{$configuracion['url']}/{$configuracion['version']}/{$identificadorArchivo}")
            ->throw();

        $urlDescarga = $metadatos->json('url');
        $tipoMime = $metadatos->json('mime_type');
        $tamanoInformado = (int) $metadatos->json('file_size', 0);
        if (! is_string($urlDescarga) || $urlDescarga === '' || ! is_string($tipoMime)) {
            throw new RuntimeException('Meta no devolvió los datos necesarios para descargar el archivo.');
        }

        $extension = self::EXTENSIONES_PERMITIDAS[$tipoMime] ?? null;
        if ($extension === null) {
            throw ValidationException::withMessages([
                'archivo' => 'El comprobante recibido no es una imagen JPG, PNG ni un archivo PDF.',
            ]);
        }

        $tamanoMaximo = max(1, (int) config('services.whatsapp.tamano_maximo_archivo_kb', 10240)) * 1024;
        if ($tamanoInformado > $tamanoMaximo) {
            throw ValidationException::withMessages([
                'archivo' => 'El comprobante recibido supera el tamaño máximo permitido.',
            ]);
        }

        $respuestaArchivo = $this->solicitud($configuracion['token'])
            ->get($urlDescarga)
            ->throw();
        $contenido = $respuestaArchivo->body();
        if ($contenido === '' || strlen($contenido) > $tamanoMaximo) {
            throw ValidationException::withMessages([
                'archivo' => 'El comprobante está vacío o supera el tamaño máximo permitido.',
            ]);
        }

        $ruta = 'whatsapp/comprobantes/'.Str::uuid().'.'.$extension;
        if (! Storage::disk('local')->put($ruta, $contenido)) {
            throw new RuntimeException('No se pudo almacenar el archivo recibido desde Meta.');
        }

        return $ruta;
    }

    /** @return array{token: string, url: string, version: string} */
    private function configuracion(): array
    {
        $token = (string) config('services.whatsapp.token_acceso');
        $url = rtrim((string) config('services.whatsapp.url_base'), '/');
        $version = (string) config('services.whatsapp.version_api');
        if ($token === '' || $url === '' || $version === '') {
            throw ValidationException::withMessages([
                'whatsapp' => 'Falta configurar la conexión con Meta para descargar archivos.',
            ]);
        }

        return compact('token', 'url', 'version');
    }

    private function solicitud(string $token): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($token)
            ->timeout(20)
            ->retry(2, 250);
    }
}
