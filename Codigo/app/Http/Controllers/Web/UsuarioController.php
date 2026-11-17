<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioUsuarios;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarUsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * UsuarioController: Controlador web para la administracion de operadores y cuentas de acceso.
 * Corresponde a los casos de uso: 'Gestionar usuarios (Registrar usuario)', 'Listar usuarios'
 * y 'Desactivar / Activar usuario' (Modulo 6).
 * Conecta con Administrador (metodo gestionarUsuarios()), Usuario (desactivar()) y ServicioUsuarios.
 */
class UsuarioController extends Controller
{
    /**
     * Lista los usuarios del sistema junto con sus perfiles de administrador o empleado.
     */
    public function index(Request $request): View
    {
        $buscar = $request->string('buscar')->trim()->toString();
        $usuarios = Usuario::with(['administrador', 'empleado'])
            ->when($buscar !== '', fn ($consulta) => $consulta->where('nombre_usuario', 'like', '%'.$buscar.'%'))
            ->orderBy('nombre_usuario')->paginate(25)->withQueryString();

        return view('usuarios.index', compact('usuarios', 'buscar'));
    }

    /**
     * Registra un nuevo usuario en la base de datos asignando credenciales y rol laboral.
     */
    public function store(GuardarUsuarioRequest $request, ServicioUsuarios $servicio): RedirectResponse
    {
        $servicio->crear($request->validated());

        return back()->with('exito', 'Usuario creado correctamente.');
    }

    public function edit(Usuario $usuario): View
    {
        return view('usuarios.formulario', ['usuario' => $usuario->load(['administrador', 'empleado'])]);
    }

    public function update(GuardarUsuarioRequest $request, Usuario $usuario, ServicioUsuarios $servicio): RedirectResponse
    {
        $servicio->actualizar($usuario, $request->validated(), $request->user());

        return redirect()->route('usuarios.index')->with('exito', 'Usuario actualizado.');
    }

    public function activar(Usuario $usuario): RedirectResponse
    {
        $usuario->activar();

        return back()->with('exito', 'Usuario activado.');
    }

    public function editarContrasena(): View
    {
        return view('usuarios.contrasena');
    }

    public function restablecerContrasena(Request $request, Usuario $usuario): RedirectResponse
    {
        abort_if($usuario->is($request->user()), 403);

        $datos = $request->validate([
            'contrasena_nueva' => ['required', 'string', 'min:8', 'regex:/\A[^\x00]{1,72}\z/', 'confirmed'],
        ]);
        $usuario->cambiarContrasena($datos['contrasena_nueva']);

        return redirect()->route('usuarios.edit', $usuario)->with('exito', 'Contraseña restablecida.');
    }

    public function actualizarContrasena(Request $request, ServicioUsuarios $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'contrasena_actual' => ['required', 'string'],
            'contrasena_nueva' => ['required', 'string', 'min:8', 'regex:/\A[^\x00]{1,72}\z/', 'confirmed', 'different:contrasena_actual'],
        ]);
        $cuenta = $request->user();
        $servicio->cambiarContrasena($cuenta, $datos['contrasena_actual'], $datos['contrasena_nueva']);
        Auth::login($cuenta->refresh());
        $request->session()->regenerate();

        return redirect()->route('cuenta.contrasena')->with('exito', 'Contraseña actualizada.');
    }

    /**
     * Desactiva la cuenta de un usuario impidiendo su inicio de sesion en el sistema.
     */
    public function destroy(Usuario $usuario): RedirectResponse
    {
        abort_if(auth()->id() === $usuario->id_usuario, 422, 'No puede desactivar su propio usuario.');
        $usuario->desactivar();

        return back()->with('exito', 'Usuario desactivado.');
    }
}
