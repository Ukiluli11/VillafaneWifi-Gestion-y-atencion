from django.contrib.auth.base_user import AbstractBaseUser
from django.contrib.auth.models import PermissionsMixin
from django.db import models

from .managers import UserManager


class User(AbstractBaseUser, PermissionsMixin):
    username = models.CharField("nombre de usuario", max_length=150, unique=True)
    is_active = models.BooleanField("activo", default=True)
    is_staff = models.BooleanField("acceso al panel", default=False)
    created_at = models.DateTimeField("fecha de alta", auto_now_add=True)

    objects = UserManager()

    USERNAME_FIELD = "username"

    class Meta:
        db_table = "usuario"
        verbose_name = "usuario"
        verbose_name_plural = "usuarios"

    def __str__(self):
        return self.username


class Employee(models.Model):
    class Area(models.TextChoices):
        ADMINISTRATION = "administration", "Administración"
        SUPPORT = "support", "Soporte técnico"
        CUSTOMER_SERVICE = "customer_service", "Atención al cliente"

    user = models.OneToOneField(
        User,
        on_delete=models.CASCADE,
        primary_key=True,
        related_name="employee_profile",
        db_column="id_usuario",
    )
    area = models.CharField("área", max_length=30, choices=Area.choices)

    class Meta:
        db_table = "empleado"
        verbose_name = "empleado"
        verbose_name_plural = "empleados"

    def __str__(self):
        return f"Empleado: {self.user.username}"


class Administrator(models.Model):
    user = models.OneToOneField(
        User,
        on_delete=models.CASCADE,
        primary_key=True,
        related_name="administrator_profile",
        db_column="id_usuario",
    )

    class Meta:
        db_table = "administrador"
        verbose_name = "administrador"
        verbose_name_plural = "administradores"

    def __str__(self):
        return f"Administrador: {self.user.username}"

