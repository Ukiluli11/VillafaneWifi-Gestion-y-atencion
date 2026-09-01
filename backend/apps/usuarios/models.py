"""Modelos de autenticación y especialización de los usuarios internos."""

from django.contrib.auth.base_user import AbstractBaseUser
from django.contrib.auth.models import PermissionsMixin
from django.db import models

from .managers import GestorUsuario


class Usuario(AbstractBaseUser, PermissionsMixin):
    """Representa la credencial común de empleados y administradores."""

    nombre_usuario = models.CharField("nombre de usuario", max_length=150, unique=True)
    is_active = models.BooleanField("activo", default=True)
    is_staff = models.BooleanField("acceso al panel", default=False)
    fecha_alta = models.DateTimeField("fecha de alta", auto_now_add=True)

    objects = GestorUsuario()

    USERNAME_FIELD = "nombre_usuario"

    class Meta:
        """Configura el nombre físico y las etiquetas administrativas del modelo."""

        db_table = "usuario"
        verbose_name = "usuario"
        verbose_name_plural = "usuarios"

    def __str__(self):
        """Devuelve el identificador legible utilizado en el panel."""

        return self.nombre_usuario


class Empleado(models.Model):
    """Especializa un usuario interno con el área donde presta tareas."""

    class Area(models.TextChoices):
        """Enumera las áreas habilitadas para clasificar empleados."""

        ADMINISTRACION = "administracion", "Administración"
        SOPORTE = "soporte", "Soporte técnico"
        ATENCION_CLIENTE = "atencion_cliente", "Atención al cliente"

    usuario = models.OneToOneField(
        Usuario,
        on_delete=models.CASCADE,
        primary_key=True,
        related_name="perfil_empleado",
        db_column="id_usuario",
    )
    area = models.CharField("área", max_length=30, choices=Area.choices)

    class Meta:
        """Configura el nombre físico y las etiquetas del subtipo empleado."""

        db_table = "empleado"
        verbose_name = "empleado"
        verbose_name_plural = "empleados"

    def __str__(self):
        """Devuelve una descripción breve del empleado."""

        return f"Empleado: {self.usuario.nombre_usuario}"


class Administrador(models.Model):
    """Especializa un usuario interno con atribuciones administrativas."""

    usuario = models.OneToOneField(
        Usuario,
        on_delete=models.CASCADE,
        primary_key=True,
        related_name="perfil_administrador",
        db_column="id_usuario",
    )

    class Meta:
        """Configura el nombre físico y las etiquetas del subtipo administrador."""

        db_table = "administrador"
        verbose_name = "administrador"
        verbose_name_plural = "administradores"

    def __str__(self):
        """Devuelve una descripción breve del administrador."""

        return f"Administrador: {self.usuario.nombre_usuario}"

