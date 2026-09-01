"""Configuración del módulo de conversaciones."""

from django.apps import AppConfig


class ConversacionesConfig(AppConfig):
    """Registra el módulo de conversaciones dentro del proyecto Django."""

    default_auto_field = "django.db.models.BigAutoField"
    name = "apps.conversaciones"
    verbose_name = "Conversaciones"

