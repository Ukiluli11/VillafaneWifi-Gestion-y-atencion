<?php

namespace App\Dominio;

use App\Models\Cliente;
use App\Models\Conversacion;

class ServicioIdentificacion
{
    public function identificarCliente(
        Conversacion $conversacion,
        string $numeroWhatsapp,
        ?string $contenidoMensaje = null,
    ): ?Cliente {
        if ($conversacion->id_cliente !== null) {
            return $conversacion->cliente;
        }

        $telefono = preg_replace('/\D+/', '', $numeroWhatsapp) ?? '';
        $cliente = $telefono !== ''
            ? Cliente::query()->where('telefono_whatsapp', $telefono)->first()
            : null;

        $identificadoPorDocumento = false;
        if ($cliente === null && $contenidoMensaje !== null) {
            $documento = $this->extraerDocumento($contenidoMensaje);
            if ($documento !== null) {
                $cliente = Cliente::query()->where('numero_documento', $documento)->first();
                $identificadoPorDocumento = $cliente !== null;
            }
        }

        if ($cliente !== null) {
            $cambios = ['id_cliente' => $cliente->id_cliente];
            if ($identificadoPorDocumento) {
                $cambios['paso_actual'] = 'identificacion_confirmada';
            }
            $conversacion->update($cambios);
            if ($identificadoPorDocumento && $telefono !== '' && $cliente->telefono_whatsapp !== $telefono) {
                $cliente->update(['telefono_whatsapp' => $telefono]);
                $cliente->refresh();
            }
            $conversacion->setRelation('cliente', $cliente);
        }

        return $cliente;
    }

    private function extraerDocumento(string $contenido): ?string
    {
        preg_match_all('/(?<!\d)(\d[\d.\s-]{5,14}\d)(?!\d)/', $contenido, $coincidencias);

        foreach ($coincidencias[1] ?? [] as $candidato) {
            $documento = preg_replace('/\D+/', '', $candidato) ?? '';
            if (strlen($documento) >= 7 && strlen($documento) <= 11) {
                return $documento;
            }
        }

        return null;
    }
}
