"""Configuración del módulo de facturación."""

from django.apps import AppConfig


class FacturacionConfig(AppConfig):
    """Registra el módulo de facturación dentro del proyecto Django."""

    default_auto_field = "django.db.models.BigAutoField"
    name = "apps.facturacion"
    verbose_name = "Facturación y cuenta corriente"

