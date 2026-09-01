"""Configuración del módulo de reportes."""

from django.apps import AppConfig


class ReportesConfig(AppConfig):
    """Registra el módulo de reportes dentro del proyecto Django."""

    default_auto_field = "django.db.models.BigAutoField"
    name = "apps.reportes"
    verbose_name = "Reportes"

