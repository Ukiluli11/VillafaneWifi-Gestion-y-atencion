"""Servicios de aplicación para crear usuarios internos con un subtipo válido."""

from django.db import transaction

from .models import Administrador, Empleado, Usuario


class ServicioUsuarios:
    """Garantiza la especialización total y disjunta de los usuarios internos."""

    @transaction.atomic
    def crear_empleado(self, nombre_usuario, contrasena, area):
        """Crea un usuario y lo especializa exclusivamente como empleado."""

        usuario = Usuario.objects.crear_usuario(nombre_usuario, contrasena)
        empleado = Empleado.objects.create(usuario=usuario, area=area)
        return empleado

    @transaction.atomic
    def crear_administrador(self, nombre_usuario, contrasena):
        """Crea un usuario y lo especializa exclusivamente como administrador."""

        usuario = Usuario.objects.crear_usuario(nombre_usuario, contrasena)
        administrador = Administrador.objects.create(usuario=usuario)
        return administrador
