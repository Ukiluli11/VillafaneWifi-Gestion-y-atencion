"""Configuración del panel administrativo para clientes y teléfonos."""

from django.contrib import admin

from .models import Cliente, TelefonoCliente


class TelefonoClienteEnLinea(admin.TabularInline):
    """Permite consultar y editar teléfonos dentro de cada cliente."""

    model = TelefonoCliente
    extra = 1


@admin.register(Cliente)
class AdministracionCliente(admin.ModelAdmin):
    """Configura búsqueda, filtros y contactos del cliente en el panel."""

    list_display = ("nombre_razon_social", "tipo_documento", "numero_documento", "estado")
    list_filter = ("estado", "tipo_cliente", "tipo_documento")
    search_fields = (
        "nombre_razon_social",
        "numero_documento",
        "telefonos__numero",
        "contacto_localidad",
    )
    inlines = (TelefonoClienteEnLinea,)
