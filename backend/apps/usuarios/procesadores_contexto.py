"""Datos de autorización disponibles en todas las plantillas del panel."""

from .politicas import ServicioAutorizacion


def acciones_del_usuario(peticion):
    """Expone las acciones habilitadas para construir una navegación segura."""

    usuario = peticion.user
    if not getattr(usuario, "is_authenticated", False):
        return {"acciones_habilitadas": frozenset()}
    return {"acciones_habilitadas": ServicioAutorizacion().acciones_permitidas(usuario)}
