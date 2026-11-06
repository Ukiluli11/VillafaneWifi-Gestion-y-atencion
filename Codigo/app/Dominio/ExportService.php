<?php

namespace App\Dominio;

use Dompdf\Dompdf;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /** @param array<string, mixed> $datos */
    public function pdf(array $datos): Response
    {
        $motor = new Dompdf(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);
        $motor->loadHtml(view('reportes.imprimir', $datos + ['esPdf' => true])->render());
        $motor->setPaper('A4', 'landscape');
        $motor->render();

        return new Response($motor->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Reporte_'.$datos['tipo'].'.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * @param  list<string>  $encabezados
     * @param  iterable<array<int, mixed>>  $filas
     */
    public function excel(string $nombre, array $encabezados, iterable $filas): StreamedResponse
    {
        return new StreamedResponse(function () use ($encabezados, $filas): void {
            $archivo = tempnam(sys_get_temp_dir(), 'reporte_');
            try {
                $writer = new Writer;
                $writer->openToFile($archivo);
                $writer->getCurrentSheet()->setName('Reporte');
                $writer->getCurrentSheet()->setColumnWidthForRange(22, 1, count($encabezados));
                $writer->addRow(Row::fromValuesWithStyle($encabezados, new Style(fontBold: true, backgroundColor: 'D9EDEE')));
                $totales = [];
                foreach ($filas as $fila) {
                    $cells = [];
                    foreach ($fila as $indice => $valor) {
                        if (in_array($encabezados[$indice], ['Precio Plan', 'Deuda Total ($)', 'Monto ($)'], true)) {
                            $valor = (float) str_replace(',', '.', str_replace('.', '', (string) $valor));
                            if ($encabezados[$indice] !== 'Precio Plan') {
                                $totales[$indice] = ($totales[$indice] ?? 0) + $valor;
                            }
                        }
                        $cells[] = is_string($valor) ? new StringCell($valor) : Cell::fromValue($valor);
                    }
                    $writer->addRow(new Row($cells));
                }
                if ($totales !== []) {
                    $filaTotal = array_fill(0, count($encabezados), '');
                    $filaTotal[0] = 'TOTAL';
                    foreach ($totales as $indice => $total) {
                        $filaTotal[$indice] = round($total, 2);
                    }
                    $writer->addRow(Row::fromValuesWithStyle($filaTotal, new Style(fontBold: true)));
                }
                $writer->close();
                readfile($archivo);
            } finally {
                if (is_file($archivo)) {
                    unlink($archivo);
                }
            }
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'.xlsx"',
            'Cache-Control' => 'no-store',
        ]);
    }
}
