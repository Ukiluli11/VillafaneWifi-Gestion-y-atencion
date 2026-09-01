from django.contrib import admin
from django.contrib.auth.admin import UserAdmin as BaseUserAdmin

from .forms import UserChangeForm, UserCreationForm
from .models import Administrator, Employee, User


@admin.register(User)
class UserAdmin(BaseUserAdmin):
    form = UserChangeForm
    add_form = UserCreationForm
    list_display = ("username", "is_active", "is_staff", "is_superuser")
    list_filter = ("is_active", "is_staff", "is_superuser")
    ordering = ("username",)
    search_fields = ("username",)
    fieldsets = (
        (None, {"fields": ("username", "password")}),
        (
            "Acceso",
            {
                "fields": (
                    "is_active",
                    "is_staff",
                    "is_superuser",
                    "groups",
                    "user_permissions",
                )
            },
        ),
        ("Auditoría", {"fields": ("last_login", "created_at")}),
    )
    add_fieldsets = (
        (
            None,
            {
                "classes": ("wide",),
                "fields": ("username", "password1", "password2", "is_active", "is_staff"),
            },
        ),
    )
    readonly_fields = ("last_login", "created_at")
    filter_horizontal = ("groups", "user_permissions")


admin.site.register(Employee)
admin.site.register(Administrator)
