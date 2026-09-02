"""Rutas REST de facturación y cuenta corriente."""

from rest_framework.routers import DefaultRouter

from .views import VistaCuotas, VistaPagos

enrutador = DefaultRouter()
enrutador.register("cuotas", VistaCuotas, basename="cuota")
enrutador.register("pagos", VistaPagos, basename="pago")

urlpatterns = enrutador.urls
