"""Rutas REST del módulo de clientes."""

from rest_framework.routers import DefaultRouter

from .views import VistaClientes

enrutador = DefaultRouter()
enrutador.register("clientes", VistaClientes, basename="cliente")

urlpatterns = enrutador.urls
