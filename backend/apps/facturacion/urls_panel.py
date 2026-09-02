"""Rutas web del módulo de facturación y cuenta corriente."""

from django.urls import path

from .vistas_panel import (
    VistaAltaCuentaReceptora,
    VistaDetalleCuentaCorriente,
    VistaGeneracionCuotas,
    VistaListaCuentasCorrientes,
    VistaListaCuentasReceptoras,
    VistaRegistroPago,
)

app_name = "facturacion-panel"

urlpatterns = [
    path("", VistaListaCuentasCorrientes.as_view(), name="lista"),
    path("generar-cuotas/", VistaGeneracionCuotas.as_view(), name="generar-cuotas"),
    path("cuentas-receptoras/", VistaListaCuentasReceptoras.as_view(), name="cuentas-receptoras"),
    path(
        "cuentas-receptoras/nueva/",
        VistaAltaCuentaReceptora.as_view(),
        name="alta-cuenta-receptora",
    ),
    path("<int:pk>/", VistaDetalleCuentaCorriente.as_view(), name="detalle"),
    path("<int:pk>/registrar-pago/", VistaRegistroPago.as_view(), name="registrar-pago"),
]
