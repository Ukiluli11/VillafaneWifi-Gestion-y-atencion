"""Configuración del módulo de comprobantes y conciliación."""

from django.apps import AppConfig


class PagosConfig(AppConfig):
    """Registra el módulo de pagos dentro del proyecto Django."""

    default_auto_field = "django.db.models.BigAutoField"
    name = "apps.pagos"
    verbose_name = "Comprobantes y conciliación"

