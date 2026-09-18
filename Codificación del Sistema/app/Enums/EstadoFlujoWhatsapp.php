<?php

namespace App\Enums;

/** Paso funcional que el bot espera dentro de una conversación. */
enum EstadoFlujoWhatsapp: string
{
    case Menu = 'menu';
    case EsperandoComprobante = 'esperando_comprobante';
    case EsperandoDescripcionReclamo = 'esperando_descripcion_reclamo';
    case EsperandoDocumento = 'esperando_documento';
    case EsperandoNombreRegistro = 'esperando_nombre_registro';
    case EsperandoDireccionContacto = 'esperando_direccion_contacto';
    case EsperandoPlanRegistro = 'esperando_plan_registro';
    case EsperandoDireccionInstalacion = 'esperando_direccion_instalacion';
    case EsperandoDiaVencimiento = 'esperando_dia_vencimiento';
}
