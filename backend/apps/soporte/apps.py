"""Configuración del módulo de soporte técnico."""

from django.apps import AppConfig


class SoporteConfig(AppConfig):
    """Registra el módulo de soporte dentro del proyecto Django."""

    default_auto_field = "django.db.models.BigAutoField"
    name = "apps.soporte"
    verbose_name = "Soporte técnico"

