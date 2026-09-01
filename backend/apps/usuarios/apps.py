"""Configuración del módulo de usuarios internos."""

from django.apps import AppConfig


class UsuariosConfig(AppConfig):
    """Registra el módulo de usuarios dentro del proyecto Django."""

    default_auto_field = "django.db.models.BigAutoField"
    name = "apps.usuarios"
    verbose_name = "Usuarios"

