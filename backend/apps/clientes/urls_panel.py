"""Rutas de las pantallas web del módulo de clientes."""

from django.urls import path

from .vistas_panel import (
    VistaAltaCliente,
    VistaBajaCliente,
    VistaDetalleCliente,
    VistaEdicionCliente,
    VistaListaClientes,
)

app_name = "clientes-panel"

urlpatterns = [
    path("", VistaListaClientes.as_view(), name="lista"),
    path("nuevo/", VistaAltaCliente.as_view(), name="alta"),
    path("<int:pk>/", VistaDetalleCliente.as_view(), name="detalle"),
    path("<int:pk>/editar/", VistaEdicionCliente.as_view(), name="editar"),
    path("<int:pk>/baja/", VistaBajaCliente.as_view(), name="baja"),
]
