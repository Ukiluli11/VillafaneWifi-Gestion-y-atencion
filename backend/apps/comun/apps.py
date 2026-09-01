"""Configuración del módulo de componentes comunes."""

from django.apps import AppConfig


class ComunConfig(AppConfig):
    """Registra el módulo común dentro del proyecto Django."""

    default_auto_field = "django.db.models.BigAutoField"
    name = "apps.comun"
    verbose_name = "Componentes comunes"

