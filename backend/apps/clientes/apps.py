"""Configuración del módulo de clientes."""

from django.apps import AppConfig


class ClientesConfig(AppConfig):
    """Registra el módulo de clientes dentro del proyecto Django."""

    default_auto_field = "django.db.models.BigAutoField"
    name = "apps.clientes"
    verbose_name = "Clientes"

