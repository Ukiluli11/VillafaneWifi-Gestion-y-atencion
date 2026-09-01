"""Define las rutas HTTP generales del sistema."""

from apps.comun.views import VistaSalud
from django.contrib import admin
from django.urls import include, path

urlpatterns = [
    path("", include("apps.usuarios.urls")),
    path("admin/", admin.site.urls),
    path("health/", VistaSalud.as_view(), name="verificar-salud"),
]
