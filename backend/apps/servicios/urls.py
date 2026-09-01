"""Rutas REST del catálogo de planes y los servicios contratados."""

from rest_framework.routers import DefaultRouter

from .views import VistaPlanes, VistaServicios

enrutador = DefaultRouter()
enrutador.register("planes", VistaPlanes, basename="plan")
enrutador.register("servicios", VistaServicios, basename="servicio")

urlpatterns = enrutador.urls
