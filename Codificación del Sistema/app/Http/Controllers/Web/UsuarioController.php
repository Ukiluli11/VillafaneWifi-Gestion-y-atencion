<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioUsuarios;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarUsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', ['usuarios' => Usuario::with(['administrador', 'empleado'])->orderBy('nombre_usuario')->get()]);
    }

    public function store(GuardarUsuarioRequest $request, ServicioUsuarios $servicio): RedirectResponse
    {
        $servicio->crear($request->validated());

        return back()->with('exito', 'Usuario creado correctamente.');
    }

    public function destroy(Usuario $usuario): RedirectResponse
    {
        abort_if(auth()->id() === $usuario->id_usuario, 422, 'No puede desactivar su propio usuario.');
        $usuario->desactivar();

        return back()->with('exito', 'Usuario desactivado.');
    }
}
