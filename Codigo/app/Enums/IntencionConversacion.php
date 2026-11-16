<?php

namespace App\Enums;

enum IntencionConversacion: string
{
    case ConsultaCuenta = 'consulta_cuenta';
    case ReclamoSoporte = 'reclamo_soporte';
    case EnvioComprobante = 'envio_comprobante';
    case DatosPago = 'datos_pago';
    case AtencionHumana = 'atencion_humana';
    case Desconocida = 'desconocida';
}
