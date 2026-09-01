"""Configura la administración de usuarios, empleados y administradores."""

from django.contrib import admin
from django.contrib.auth.admin import UserAdmin as AdministracionBaseUsuario

from .forms import FormularioCambioUsuario, FormularioCreacionUsuario
from .models import Administrador, Empleado, Usuario


@admin.register(Usuario)
class AdministracionUsuario(AdministracionBaseUsuario):
    """Personaliza las pantallas de alta, búsqueda y edición de usuarios."""

    form = FormularioCambioUsuario
    add_form = FormularioCreacionUsuario
    list_display = ("nombre_usuario", "is_active", "is_staff", "is_superuser")
    list_filter = ("is_active", "is_staff", "is_superuser")
    ordering = ("nombre_usuario",)
    search_fields = ("nombre_usuario",)
    fieldsets = (
        (None, {"fields": ("nombre_usuario", "password")}),
        (
            "Acceso",
            {
                "fields": (
                    "is_active",
                    "is_staff",
                    "is_superuser",
                )
            },
        ),
        ("Auditoría", {"fields": ("last_login", "fecha_alta")}),
    )
    add_fieldsets = (
        (
            None,
            {
                "classes": ("wide",),
                "fields": (
                    "nombre_usuario",
                    "contrasena1",
                    "contrasena2",
                    "is_active",
                    "is_staff",
                ),
            },
        ),
    )
    readonly_fields = ("last_login", "fecha_alta")
    filter_horizontal = ()


admin.site.register(Empleado)
admin.site.register(Administrador)
