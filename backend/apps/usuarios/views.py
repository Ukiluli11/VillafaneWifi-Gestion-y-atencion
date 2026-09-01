"""Vistas orientadas a objetos para autenticación y navegación del usuario interno."""

from django.contrib.auth.mixins import LoginRequiredMixin
from django.contrib.auth.views import LoginView, LogoutView
from django.views.generic import TemplateView

from apps.clientes.models import Cliente
from apps.servicios.models import Plan, Servicio

from .politicas import AccionSistema, ServicioAutorizacion


class VistaInicioSesion(LoginView):
    """Autentica a empleados y administradores mediante nombre de usuario y contraseña."""

    template_name = "usuarios/iniciar_sesion.html"
    redirect_authenticated_user = True


class VistaCerrarSesion(LogoutView):
    """Finaliza de forma segura la sesión del usuario autenticado."""

    next_page = "usuarios:iniciar-sesion"


class VistaInicio(LoginRequiredMixin, TemplateView):
    """Muestra la pantalla inicial protegida del panel interno."""

    template_name = "usuarios/inicio.html"

    def get_context_data(self, **kwargs):
        """Calcula únicamente los indicadores que el usuario puede consultar."""

        contexto = super().get_context_data(**kwargs)
        autorizacion = ServicioAutorizacion()
        resumen = {"clientes": None, "planes": None, "servicios": None}
        if autorizacion.puede(self.request.user, AccionSistema.CONSULTAR_CLIENTES):
            resumen["clientes"] = Cliente.objects.filter(estado=Cliente.Estado.ACTIVO).count()
        if autorizacion.puede(self.request.user, AccionSistema.CONSULTAR_PLANES):
            resumen["planes"] = Plan.objects.filter(estado=Plan.Estado.ACTIVO).count()
        if autorizacion.puede(self.request.user, AccionSistema.CONSULTAR_SERVICIOS):
            resumen["servicios"] = Servicio.objects.filter(estado=Servicio.Estado.ACTIVO).count()
        contexto["resumen"] = resumen
        return contexto
