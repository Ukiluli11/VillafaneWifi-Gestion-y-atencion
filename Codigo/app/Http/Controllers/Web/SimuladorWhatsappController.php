<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioAtencionWhatsapp;
use App\Dominio\ServicioConversaciones;
use App\Dominio\ServicioIdentificacion;
use App\Dominio\WhatsAppService;
use App\Enums\EstadoEnvioMensaje;
use App\Http\Controllers\Controller;
use App\Http\Requests\SimularMensajeWhatsappRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SimuladorWhatsappController extends Controller
{
    public function create(): View
    {
        abort_unless(config('services.whatsapp.modo_simulacion'), 404);

        return view('simulador-whatsapp.crear');
    }

    public function store(
        SimularMensajeWhatsappRequest $request,
        ServicioConversaciones $conversaciones,
        ServicioIdentificacion $identificacion,
        ServicioAtencionWhatsapp $atencion,
        WhatsAppService $whatsApp,
    ): RedirectResponse {
        $datos = $request->validated();
        $telefono = $conversaciones->normalizarNumeroWhatsapp($datos['telefono']);
        $tipo = $datos['tipo'];
        $nombreArchivo = $tipo === 'imagen' ? 'comprobante-simulado.png' : 'comprobante-simulado.pdf';
        $evento = [
            'id_externo' => 'wamid.simulado.entrada.'.Str::uuid(),
            'telefono' => $telefono,
            'tipo' => $tipo,
            'contenido' => $tipo === 'texto' ? trim((string) $datos['contenido']) : $nombreArchivo,
            'referencia_archivo' => $tipo === 'texto' ? null : 'meta://simulado-'.$tipo.'-'.Str::uuid(),
            'fecha_hora' => CarbonImmutable::now(),
        ];

        $conversacion = $conversaciones->obtenerOCrearConversacion($telefono);
        $mensaje = $conversaciones->registrarMensajeEntrante($conversacion, $evento);
        $cliente = $identificacion->identificarCliente($conversacion, $telefono, $evento['contenido']);
        $respuesta = $atencion->procesarMensaje($conversacion, $mensaje, $cliente);

        if ($respuesta !== null) {
            $idExterno = $whatsApp->enviarMensaje($telefono, $respuesta);
            $conversaciones->registrarMensajeSaliente(
                $conversacion,
                $respuesta,
                $idExterno,
                EstadoEnvioMensaje::Enviado,
            );
        }

        return redirect()
            ->route('conversaciones.show', $conversacion)
            ->with('exito', 'Mensaje simulado procesado sin utilizar Meta ni OpenAI.');
    }
}
