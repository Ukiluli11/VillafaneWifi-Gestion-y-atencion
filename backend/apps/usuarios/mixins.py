"""Componentes reutilizables para proteger vistas según una acción funcional."""

from django.contrib.auth.mixins import AccessMixin
from django.core.exceptions import ImproperlyConfigured, PermissionDenied

from .politicas import AccionSistema, ServicioAutorizacion


class AccionRequeridaMixin(AccessMixin):
    """Restringe una vista a usuarios autorizados para una acción de RF-30."""

    accion_requerida: AccionSistema | None = None

    def dispatch(self, request, *args, **kwargs):
        """Comprueba la sesión y la política antes de ejecutar la vista."""

        if not request.user.is_authenticated:
            return self.handle_no_permission()
        if self.accion_requerida is None:
            raise ImproperlyConfigured("La vista debe declarar 'accion_requerida'.")
        if not ServicioAutorizacion().puede(request.user, self.accion_requerida):
            raise PermissionDenied("No posee acceso a esta función del sistema.")
        return super().dispatch(request, *args, **kwargs)
