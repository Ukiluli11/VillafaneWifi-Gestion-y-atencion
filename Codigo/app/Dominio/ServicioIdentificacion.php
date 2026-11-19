<?php

namespace App\Dominio;

use App\Models\Cliente;
use App\Models\Conversacion;

/**
 * Vincula la conversación de WhatsApp con el cliente identificado.
 */
class ServicioIdentificacion
{
    public function identificarCliente(Conversacion $conversacion, string $numeroWhatsapp): ?Cliente
    {
        $cliente = $this->clientePorTelefono($numeroWhatsapp);
        if ($cliente === null
            || ($conversacion->id_cliente !== null && $conversacion->id_cliente !== $cliente->id_cliente)) {
            return null;
        }

        if ($conversacion->id_cliente === null) {
            $conversacion->update(['id_cliente' => $cliente->id_cliente]);
        }
        $conversacion->setRelation('cliente', $cliente);

        return $cliente;
    }

    public function clientePorTelefono(string $numeroWhatsapp): ?Cliente
    {
        $telefonos = self::formatosTelefono($numeroWhatsapp);
        if ($telefonos === []) {
            return null;
        }

        $clientes = Cliente::query()->whereIn('telefono_whatsapp', $telefonos)->limit(2)->get();

        return $clientes->count() === 1 ? $clientes->first() : null;
    }

    /** @return list<string> */
    public static function formatosTelefono(string $numero): array
    {
        $numero = preg_replace('/\D+/', '', $numero) ?? '';
        if ($numero === '') {
            return [];
        }
        if (preg_match('/^549\d{10}$/D', $numero)) {
            return [$numero, '54'.substr($numero, 3)];
        }
        if (preg_match('/^54\d{10}$/D', $numero)) {
            return [$numero, '549'.substr($numero, 2)];
        }

        return [$numero];
    }
}
