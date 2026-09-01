"""Rutas de las pantallas web de planes y servicios."""

from django.urls import path

from .vistas_panel import (
    VistaAltaPlan,
    VistaAltaServicio,
    VistaBajaPlan,
    VistaBajaServicio,
    VistaEdicionPlan,
    VistaEdicionServicio,
    VistaListaPlanes,
    VistaListaServicios,
)

app_name = "servicios-panel"

urlpatterns = [
    path("planes/", VistaListaPlanes.as_view(), name="planes"),
    path("planes/nuevo/", VistaAltaPlan.as_view(), name="alta-plan"),
    path("planes/<int:pk>/editar/", VistaEdicionPlan.as_view(), name="editar-plan"),
    path("planes/<int:pk>/baja/", VistaBajaPlan.as_view(), name="baja-plan"),
    path("conexiones/", VistaListaServicios.as_view(), name="servicios"),
    path("conexiones/nueva/", VistaAltaServicio.as_view(), name="alta-servicio"),
    path(
        "conexiones/<int:pk>/editar/",
        VistaEdicionServicio.as_view(),
        name="editar-servicio",
    ),
    path(
        "conexiones/<int:pk>/baja/",
        VistaBajaServicio.as_view(),
        name="baja-servicio",
    ),
]
