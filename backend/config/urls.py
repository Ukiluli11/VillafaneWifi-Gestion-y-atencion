"""Define las rutas HTTP generales del sistema."""

from apps.comun.views import VistaSalud
from django.contrib import admin
from django.urls import include, path

urlpatterns = [
    path("", include("apps.usuarios.urls")),
    path("panel/clientes/", include("apps.clientes.urls_panel")),
    path("panel/cuentas/", include("apps.facturacion.urls_panel")),
    path("panel/", include("apps.servicios.urls_panel")),
    path("api/", include("apps.clientes.urls")),
    path("api/", include("apps.servicios.urls")),
    path("api/", include("apps.facturacion.urls")),
    path("admin/", admin.site.urls),
    path("health/", VistaSalud.as_view(), name="verificar-salud"),
]
