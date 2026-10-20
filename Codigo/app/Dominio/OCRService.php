<?php

namespace App\Dominio;

use App\Jobs\ProcesarOcrComprobante;
use App\Models\Comprobante;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OCRService
{
    public function estaConfigurado(): bool
    {
        return (bool) config('services.ocr.habilitado') && filled(config('services.ocr.tesseract_bin'));
    }

    public function encolarLectura(Comprobante $comprobante): void
    {
        try {
            ProcesarOcrComprobante::dispatch($comprobante->id_comprobante)->onConnection('database')->afterCommit();
        } catch (Throwable) {
            $comprobante->update(['estado_ocr' => 'fallido', 'error_ocr' => 'El archivo fue guardado, pero no se pudo programar su lectura. Podés reintentar.']);
        }
    }

    /** @return array{numero_operacion: ?string, monto: ?string, fecha: ?string} */
    public function procesarArchivo(string $rutaOContenido, ?string $textoDirecto = null): array
    {
        if ($textoDirecto === null) {
            $textoDirecto = $this->leerConTesseract($rutaOContenido);
        }

        return [
            'numero_operacion' => $this->extraerNumeroOperacion($textoDirecto),
            'monto' => $this->extraerMonto($textoDirecto),
            'fecha' => $this->extraerFecha($textoDirecto),
        ];
    }

    private function leerConTesseract(string $ruta): string
    {
        if (! $this->estaConfigurado() || ! is_file($ruta)) {
            throw new RuntimeException('El OCR local no está habilitado o el archivo no está disponible.');
        }
        $tamanio = filesize($ruta);
        if ($tamanio === false || $tamanio === 0 || $tamanio > 10 * 1024 * 1024) {
            throw new RuntimeException('El archivo está vacío o supera los 10 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($ruta);
        if (! in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) {
            throw new RuntimeException('El formato no es compatible con la lectura automática.');
        }
        $directorio = null;
        $imagen = $ruta;
        try {
            if ($mime === 'application/pdf') {
                $directorio = storage_path('app/private/ocr-temporales/'.Str::uuid());
                if (! mkdir($directorio, 0700, true) && ! is_dir($directorio)) {
                    throw new RuntimeException('No se pudo preparar la lectura del PDF.');
                }
                $conversion = Process::timeout(15)->env(['OMP_THREAD_LIMIT' => '1'])->run([
                    (string) config('services.ocr.pdftoppm_bin', 'pdftoppm'),
                    '-f', '1', '-l', '1', '-singlefile', '-scale-to', '2000', '-png',
                    $ruta, $directorio.'/pagina',
                ]);
                $imagen = $directorio.'/pagina.png';
                if (! $conversion->successful() || ! is_file($imagen)) {
                    throw new RuntimeException('No se pudo leer la primera página del PDF.');
                }
            }
            $dimensiones = @getimagesize($imagen);
            if ($dimensiones === false || $dimensiones[0] * $dimensiones[1] > 16000000) {
                throw new RuntimeException('La imagen es inválida o supera las dimensiones admitidas.');
            }
            $resultado = Process::timeout(min(30, max(1, (int) config('services.ocr.timeout', 25))))
                ->env(['OMP_THREAD_LIMIT' => '1'])->run([
                    (string) config('services.ocr.tesseract_bin', 'tesseract'), $imagen, 'stdout',
                    '-l', 'spa+eng', '--oem', '1', '--psm', '6',
                ]);
            if (! $resultado->successful() || trim($resultado->output()) === '') {
                throw new RuntimeException('No se detectó texto legible. Revisá el original o completá los datos manualmente.');
            }

            return $resultado->output();
        } finally {
            if ($directorio !== null) {
                if (is_file($directorio.'/pagina.png')) {
                    unlink($directorio.'/pagina.png');
                }
                if (is_dir($directorio)) {
                    rmdir($directorio);
                }
            }
        }
    }

    public function extraerNumeroOperacion(?string $texto): ?string
    {
        if (preg_match('/(?:operaci[oó]n|transacci[oó]n|transferencia|comprobante|\bop)\s*(?:n[uú]mero|nro\.?|n[°ºo.]|#|:)?\s*([0-9]{6,30})\b/iu', $texto ?? '', $coincidencias)) {
            return $coincidencias[1];
        }

        $lineas = preg_split('/\R/u', $texto ?? '') ?: [];
        foreach ($lineas as $indice => $linea) {
            if (preg_match('/^\s*Estado\b/iu', $linea)
                || ! preg_match('/\b(?:Coelsa\s*ID|(?:ID|c[oó]digo|n[uú]mero|nro\.?)\s*(?:de\s+(?:la\s+)?)?(?:operaci[oó]n|transacci[oó]n|transferencia)|operaci[oó]n)\b/iu', $linea, $etiqueta, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $resto = substr($linea, $etiqueta[0][1] + strlen($etiqueta[0][0]));
            $candidatas = array_merge([$resto], array_slice($lineas, $indice + 1, 3));
            foreach ($candidatas as $candidata) {
                if (preg_match('/\b(?:Estado|CUIT|CUIL|DNI|CBU|CVU|Destinatario|Moneda|Monto)\b/iu', $candidata)) {
                    break;
                }
                preg_match_all('/\b[A-Z0-9][A-Z0-9-]{5,49}\b/u', mb_strtoupper($candidata), $tokens);
                foreach ($tokens[0] as $token) {
                    if (preg_match('/\d/', $token) && strlen($token) <= 50) {
                        return $token;
                    }
                }
            }
        }

        return null;
    }

    public function extraerMonto(?string $texto): ?string
    {
        if (! preg_match('/(?:\b(?:total|monto|importe|pagado)(?:\s+(?:enviado|transferido|total))?\s*:?\s*(?:ARS\s*)?\$?\s*|(?:ARS\s*)?\$\s*)(\d[\d.,]*)(?!\d)/iu', $texto ?? '', $coincidencias)) {
            return null;
        }
        $valor = rtrim($coincidencias[1], '.,');
        if (str_contains($valor, ',') && str_contains($valor, '.')) {
            $decimal = strrpos($valor, ',') > strrpos($valor, '.') ? ',' : '.';
            $valor = str_replace($decimal === ',' ? '.' : ',', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } elseif (str_contains($valor, ',') || str_contains($valor, '.')) {
            $separador = str_contains($valor, ',') ? ',' : '.';
            $partes = explode($separador, $valor);
            if (count($partes) === 2 && strlen($partes[1]) <= 2) {
                $valor = str_replace(',', '.', $valor);
            } elseif (preg_match('/^\d{1,3}(?:[.,]\d{3})+$/', $valor)) {
                $valor = str_replace([',', '.'], '', $valor);
            } else {
                return null;
            }
        }
        if (! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $valor) || (float) $valor <= 0) {
            return null;
        }

        return number_format((float) $valor, 2, '.', '');
    }

    public function extraerFecha(?string $texto): ?string
    {
        if (preg_match('/\b(\d{4})[-\/](\d{2})[-\/](\d{2})\b/', $texto ?? '', $partes)) {
            [$anio, $mes, $dia] = [(int) $partes[1], (int) $partes[2], (int) $partes[3]];
        } elseif (preg_match('/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})\b/', $texto ?? '', $partes)) {
            [$anio, $mes, $dia] = [(int) $partes[3], (int) $partes[2], (int) $partes[1]];
        } elseif (preg_match('/\b(\d{1,2})\s+(?:de\s+)?(ene(?:ro)?|feb(?:rero)?|mar(?:zo)?|abr(?:il)?|may(?:o)?|jun(?:io)?|jul(?:io)?|ago(?:sto)?|sep(?:t(?:iembre)?)?|set(?:iembre)?|oct(?:ubre)?|nov(?:iembre)?|dic(?:iembre)?)\.?\s+(?:de\s+)?(\d{4})\b/iu', $texto ?? '', $partes)) {
            $meses = ['ene' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4, 'may' => 5, 'jun' => 6,
                'jul' => 7, 'ago' => 8, 'sep' => 9, 'set' => 9, 'oct' => 10, 'nov' => 11, 'dic' => 12];
            [$anio, $mes, $dia] = [(int) $partes[3], $meses[mb_substr(mb_strtolower($partes[2]), 0, 3)], (int) $partes[1]];
        } else {
            return null;
        }

        return checkdate($mes, $dia, $anio) ? sprintf('%04d-%02d-%02d', $anio, $mes, $dia) : null;
    }

    /** La presencia de campos mide completitud, no certeza de una transferencia. */
    public function calcularConfianza(array $datosExtraidos): float
    {
        return round((filled($datosExtraidos['numero_operacion'] ?? null) ? 0.4 : 0)
            + (filled($datosExtraidos['monto'] ?? null) ? 0.4 : 0)
            + (filled($datosExtraidos['fecha'] ?? null) ? 0.2 : 0), 2);
    }
}
