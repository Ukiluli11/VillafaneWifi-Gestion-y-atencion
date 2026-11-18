<?php

namespace App\Dominio;

use App\Enums\OpcionMenuWhatsapp;
use Carbon\CarbonImmutable;
use DateTimeInterface;

final class MenuCoquito
{
    public static function saludo(DateTimeInterface $fecha, ?string $nombre = null): string
    {
        $hora = CarbonImmutable::instance($fecha)->setTimezone('America/Argentina/Buenos_Aires')->hour;
        $franja = match (true) {
            $hora >= 6 && $hora < 12 => 'Buenos días',
            $hora >= 12 && $hora < 20 => 'Buenas tardes',
            default => 'Buenas noches',
        };
        $destinatario = $nombre === null ? '' : ", {$nombre}";

        return "¡{$franja}{$destinatario}! Soy Coquito, el asistente virtual de Villafañe Wifi.";
    }

    public static function opcionesCliente(): string
    {
        $opciones = array_map(
            fn (OpcionMenuWhatsapp $opcion): string => $opcion->value.'. '.$opcion->etiqueta(),
            OpcionMenuWhatsapp::cases(),
        );

        return "Elegí una opción o escribime tu consulta con tus propias palabras.\n\n".implode("\n", $opciones);
    }

    public static function opcionesSinRegistro(): string
    {
        return "No encontré un cliente asociado a este número.\n\n"
            ."• Escribí REGISTRARME si querés darte de alta y contratar un plan.\n"
            .'• Escribí OPERADOR si ya sos cliente y necesitás verificar tu identidad. También podés escribirnos desde tu número registrado.';
    }
}
