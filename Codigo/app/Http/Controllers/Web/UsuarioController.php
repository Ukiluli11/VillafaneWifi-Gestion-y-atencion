<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioUsuarios;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarUsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
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
    public function index(): View
    {
        return view('usuarios.index', ['usuarios' => Usuario::with(['administrador', 'empleado'])->orderBy('nombre_usuario')->get()]);
    }

    /**
     * Registra un nuevo usuario en la base de datos asignando credenciales y rol laboral.
     */
    public function store(GuardarUsuarioRequest $request, ServicioUsuarios $servicio): RedirectResponse
    {
        $servicio->crear($request->validated());

        return back()->with('exito', 'Usuario creado correctamente.');
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
