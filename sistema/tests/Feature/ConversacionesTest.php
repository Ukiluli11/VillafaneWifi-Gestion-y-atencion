<?php

namespace Tests\Feature;

use App\Dominio\ServicioConversaciones;
use App\Enums\EstadoConversacion;
use App\Enums\EstadoEnvioMensaje;
use App\Enums\ModoAtencion;
use App\Enums\TipoEmisorMensaje;
use App\Enums\TipoMensaje;
use App\Models\Administrador;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConversacionesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_inicia_una_conversacion_y_reutiliza_la_que_permanece_activa(): void
    {
        $cliente = Cliente::factory()->create();
        $servicio = app(ServicioConversaciones::class);

        $primera = $servicio->obtenerOIniciar($cliente, '+54 9 3704 123456');
        $segunda = $servicio->obtenerOIniciar($cliente, '5493704123456');

        $this->assertSame($primera->id_conversacion, $segunda->id_conversacion);
        $this->assertSame('5493704123456', $primera->numero_whatsapp);
        $this->assertSame(1, Conversacion::count());
    }

    public function test_registra_un_mensaje_entrante_una_sola_vez_por_identificador_externo(): void
    {
        $conversacion = Conversacion::factory()->create();
        $servicio = app(ServicioConversaciones::class);

        $primero = $servicio->registrarEntrante($conversacion, TipoMensaje::Texto, 'Hola', identificadorExterno: 'wamid.123');
        $repetido = $servicio->registrarEntrante($conversacion, TipoMensaje::Texto, 'Hola', identificadorExterno: 'wamid.123');

        $this->assertSame($primero->id_mensaje, $repetido->id_mensaje);
        $this->assertSame(EstadoEnvioMensaje::Recibido, $primero->estado_envio);
        $this->assertSame(1, Mensaje::count());
    }

    public function test_un_administrador_puede_tomar_y_cerrar_una_conversacion(): void
    {
        $administrador = Administrador::factory()->create();
        $conversacion = Conversacion::factory()->create();
        $servicio = app(ServicioConversaciones::class);

        $servicio->derivarAUsuario($conversacion, $administrador->usuario);
        $servicio->registrarSaliente(
            $conversacion->refresh(),
            TipoEmisorMensaje::UsuarioInterno,
            TipoMensaje::Texto,
            'Hola, soy quien continuará la atención.',
            usuario: $administrador->usuario,
        );
        $cerrada = $servicio->cerrar($conversacion->refresh());

        $this->assertSame(ModoAtencion::UsuarioInterno, $cerrada->modo_atencion);
        $this->assertSame(EstadoConversacion::Cerrada, $cerrada->estado);
        $this->assertNotNull($cerrada->fecha_hora_cierre);
        $this->assertDatabaseHas('mensaje', [
            'id_usuario' => $administrador->id_administrador,
            'tipo_emisor' => TipoEmisorMensaje::UsuarioInterno->value,
        ]);
    }

    public function test_rechaza_mensajes_en_una_conversacion_cerrada(): void
    {
        $conversacion = Conversacion::factory()->create([
            'estado' => EstadoConversacion::Cerrada,
            'fecha_hora_cierre' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(ServicioConversaciones::class)->registrarEntrante($conversacion, TipoMensaje::Texto, 'Mensaje tardío');
    }
}
