"""Configuración administrativa de las entidades de facturación."""

from django.contrib import admin

from .models import CuentaReceptora, Cuota, Pago

admin.site.register(CuentaReceptora)
admin.site.register(Cuota)
admin.site.register(Pago)
