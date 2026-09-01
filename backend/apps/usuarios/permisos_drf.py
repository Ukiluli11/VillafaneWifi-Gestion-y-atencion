"""Adaptadores de las políticas de acceso para vistas de Django REST Framework."""

from rest_framework.permissions import BasePermission

from .politicas import AccionSistema, ServicioAutorizacion


class PermisoAccionSistema(BasePermission):
    """Exige que la vista declare una acción funcional permitida al usuario."""

    message = "No posee acceso a esta función del sistema."

    def has_permission(self, request, view):
        """Evalúa la política asociada a la operación actual de la API."""

        accion = view.obtener_accion_requerida()
        if not isinstance(accion, AccionSistema):
            return False
        return ServicioAutorizacion().puede(request.user, accion)


class AccionesApiMixin:
    """Relaciona las operaciones estándar de una vista con acciones del sistema."""

    acciones_por_operacion: dict[str, AccionSistema] = {}

    def obtener_accion_requerida(self) -> AccionSistema | None:
        """Devuelve la acción configurada para la operación solicitada."""

        return self.acciones_por_operacion.get(getattr(self, "action", ""))
