<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioRecepcionWhatsapp;
use App\Http\Controllers\Controller;
use App\Http\Requests\SimularMensajeWhatsappRequest;
use App\Models\Cliente;
use App\Models\Mensaje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Permite recorrer el flujo del bot sin enviar datos a Meta. */
class SimuladorWhatsappController extends Controller
{
    public function create(): View
    {
        abort_unless(config('services.whatsapp.modo_simulacion'), 404);

        return view('simulador-whatsapp.crear', [
            'contactos' => Cliente::query()
                ->whereNotNull('telefono_whatsapp')
                ->orderBy('nombre_razon_social')
                ->get(['nombre_razon_social', 'telefono_whatsapp']),
        ]);
    }

    public function store(
        SimularMensajeWhatsappRequest $request,
        ServicioRecepcionWhatsapp $servicio,
    ): RedirectResponse {
        $datos = $request->validated();
        $numero = preg_replace('/\D+/', '', $datos['numero_whatsapp']) ?? '';
        $identificador = 'wamid.simulado.entrada.'.Str::uuid();
        $mensaje = $this->crearMensaje($datos['tipo'], $datos['contenido'], $numero, $identificador);
        $servicio->procesar([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => ['messaging_product' => 'whatsapp', 'messages' => [$mensaje]],
                ]],
            ]],
        ]);

        $mensajeGuardado = Mensaje::query()
            ->where('id_mensaje_externo', $identificador)
            ->firstOrFail();

        return redirect()
            ->route('conversaciones.show', $mensajeGuardado->id_conversacion)
            ->with('exito', 'Mensaje simulado procesado. No se envió información a Meta.');
    }

    /** @return array<string, mixed> */
    private function crearMensaje(string $tipo, string $contenido, string $numero, string $identificador): array
    {
        $mensaje = [
            'from' => $numero,
            'id' => $identificador,
            'timestamp' => (string) now()->timestamp,
            'type' => $tipo,
        ];

        return match ($tipo) {
            'image' => $mensaje + ['image' => [
                'id' => 'media.simulada.'.Str::uuid(),
                'caption' => $contenido,
            ]],
            'document' => $mensaje + ['document' => [
                'id' => 'media.simulada.'.Str::uuid(),
                'filename' => $contenido,
            ]],
            default => $mensaje + ['text' => ['body' => $contenido]],
        };
    }
}
