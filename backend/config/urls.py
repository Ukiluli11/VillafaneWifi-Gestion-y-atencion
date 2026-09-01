"""Define las rutas HTTP generales del sistema."""

from django.contrib import admin
from django.urls import path

from apps.comun.views import verificar_salud

urlpatterns = [
    path("admin/", admin.site.urls),
    path("health/", verificar_salud, name="verificar-salud"),
]
