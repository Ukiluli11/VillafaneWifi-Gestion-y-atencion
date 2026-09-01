"""Rutas de autenticación y navegación del módulo de usuarios."""

from django.urls import path

from .views import VistaCerrarSesion, VistaInicio, VistaInicioSesion

app_name = "usuarios"

urlpatterns = [
    path("", VistaInicio.as_view(), name="inicio"),
    path("iniciar-sesion/", VistaInicioSesion.as_view(), name="iniciar-sesion"),
    path("cerrar-sesion/", VistaCerrarSesion.as_view(), name="cerrar-sesion"),
]

