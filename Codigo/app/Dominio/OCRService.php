<?php

namespace App\Dominio;

use Carbon\CarbonImmutable;

class OCRService
{
    /**
     * Procesa el contenido o ruta de un archivo para extraer datos clave del comprobante.
     *
     * @return array{numero_operacion: ?string, monto: ?string, fecha: ?string}
     */
    public function procesarArchivo(string $rutaOContenido, ?string $textoDirecto = null): array
    {
        $texto = $textoDirecto;

        if ($texto === null && file_exists($rutaOContenido)) {
            // Intenta leer texto plano o metadatos del archivo
            $rawContent = @file_get_contents($rutaOContenido);
            if ($rawContent !== false) {
                $texto = $this->extraerCadenasImprimibles($rawContent);
            }
        } elseif ($texto === null) {
            $texto = $this->extraerCadenasImprimibles($rutaOContenido);
        }

        return [
            'numero_operacion' => $this->extraerNumeroOperacion($texto),
            'monto' => $this->extraerMonto($texto),
            'fecha' => $this->extraerFecha($texto),
        ];
    }

    /**
     * Extrae el número de comprobante u operación (ej. Mercado Pago, transferencias).
     */
    public function extraerNumeroOperacion(?string $texto): ?string
    {
        if (empty($texto)) {
            return null;
        }

        // Patrones habituales de Mercado Pago y transferencias bancarias:
        // "Operación #12345678901", "Op: 12345678", "Transferencia N° 123456789", "N°: 12345678"
        $patrones = [
            '/(?:operaci[oó]n|op|transf(?:erencia)?|comprobante|c[oó]d(?:igo)?)\s*(?:n[°o#:\s]*)?([0-9]{6,16})/i',
            '/#\s*([0-9]{8,16})/',
            '/\b([0-9]{8,14})\b/',
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $texto, $coincidencias)) {
                return trim($coincidencias[1]);
            }
        }

        return null;
    }

    /**
     * Extrae el importe pagado detectado en el comprobante.
     */
    public function extraerMonto(?string $texto): ?string
    {
        if (empty($texto)) {
            return null;
        }

        // Ejemplos: "$ 15.000,00", "$15000", "Total: 15.000", "Monto: $ 8.500,50"
        $patrones = [
            '/(?:total|monto|importe|pagado)?\s*\$\s*([0-9]{1,3}(?:\.[0-9]{3})+(?:,[0-9]{2})?)/i',
            '/\$\s*([0-9]+(?:,[0-9]{2})?)/i',
            '/(?:total|monto|importe)\s*:?\s*([0-9]+(?:\.[0-9]{2})?)/i',
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $texto, $coincidencias)) {
                $valor = trim($coincidencias[1]);
                // Normaliza formato argentino 15.000,00 a decimal estándar 15000.00
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);

                return number_format((float) $valor, 2, '.', '');
            }
        }

        return null;
    }

    /**
     * Extrae la fecha de pago del comprobante.
     */
    public function extraerFecha(?string $texto): ?string
    {
        if (empty($texto)) {
            return null;
        }

        // Formatos DD/MM/AAAA o AAAA-MM-DD
        if (preg_match('/(\b\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4}\b)/', $texto, $coincidencias)) {
            $dia = str_pad($coincidencias[1], 2, '0', STR_PAD_LEFT);
            $mes = str_pad($coincidencias[2], 2, '0', STR_PAD_LEFT);
            $anio = $coincidencias[3];

            try {
                return CarbonImmutable::createFromFormat('Y-m-d', "{$anio}-{$mes}-{$dia}")->toDateString();
            } catch (\Throwable) {
                // Siguiente intento
            }
        }

        if (preg_match('/(\b\d{4})[\/\-](\d{2})[\/\-](\d{2}\b)/', $texto, $coincidencias)) {
            return "{$coincidencias[1]}-{$coincidencias[2]}-{$coincidencias[3]}";
        }

        return null;
    }

    /**
     * Filtra caracteres imprimibles para extraer texto bruto desde archivos binarios.
     */
    private function extraerCadenasImprimibles(string $contenido): string
    {
        // Extrae secuencias legibles ASCII/UTF-8
        preg_match_all('/[a-zA-Z0-9$#.,:\/ \-]{3,}/', $contenido, $matches);

        return implode(' ', $matches[0] ?? []);
    }
}
