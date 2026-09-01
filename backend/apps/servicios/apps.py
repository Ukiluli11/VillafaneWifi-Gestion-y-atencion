"""Configuración del módulo de planes y servicios."""

from django.apps import AppConfig


class ServiciosConfig(AppConfig):
    """Registra el módulo de servicios dentro del proyecto Django."""

    default_auto_field = "django.db.models.BigAutoField"
    name = "apps.servicios"
    verbose_name = "Planes y servicios"

