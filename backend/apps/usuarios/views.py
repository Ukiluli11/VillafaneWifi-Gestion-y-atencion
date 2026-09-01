"""Vistas orientadas a objetos para autenticación y navegación del usuario interno."""

from django.contrib.auth.mixins import LoginRequiredMixin
from django.contrib.auth.views import LoginView, LogoutView
from django.views.generic import TemplateView


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

