"""Configuración administrativa del catálogo y las conexiones."""

from django.contrib import admin

from .models import Plan, Servicio


@admin.register(Plan)
class AdministracionPlan(admin.ModelAdmin):
    """Facilita la consulta y edición del catálogo de planes."""

    list_display = ("nombre", "velocidad_mbps", "precio_vigente", "estado")
    list_filter = ("estado",)
    search_fields = ("nombre",)


@admin.register(Servicio)
class AdministracionServicio(admin.ModelAdmin):
    """Facilita el control de conexiones por cliente, IP, MAC y estado."""

    list_display = ("id", "cliente", "plan", "ip", "mac", "estado")
    list_filter = ("estado", "plan")
    search_fields = ("cliente__nombre_razon_social", "ip", "mac")
