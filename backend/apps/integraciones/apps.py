"""Configuración del módulo de integraciones externas."""

from django.apps import AppConfig


class IntegracionesConfig(AppConfig):
    """Registra el módulo de integraciones dentro del proyecto Django."""

    default_auto_field = "django.db.models.BigAutoField"
    name = "apps.integraciones"
    verbose_name = "Integraciones externas"

