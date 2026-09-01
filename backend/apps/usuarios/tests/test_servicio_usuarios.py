"""Pruebas de la creación orientada a objetos de los subtipos de usuario."""

import pytest

from apps.usuarios.models import Administrador, Empleado, Usuario
from apps.usuarios.servicios import ServicioUsuarios


@pytest.mark.django_db
class TestServicioUsuarios:
    """Comprueba que cada usuario creado posea un único subtipo."""

    def setup_method(self):
        """Construye el servicio utilizado en cada escenario."""

        self.servicio = ServicioUsuarios()

    def test_crear_empleado_genera_usuario_y_perfil(self):
        """Crea conjuntamente la credencial y el perfil de empleado."""

        empleado = self.servicio.crear_empleado(
            "soporte1",
            "clave-segura-123",
            Empleado.Area.SOPORTE,
        )

        assert empleado.usuario.check_password("clave-segura-123")
        assert empleado.area == Empleado.Area.SOPORTE
        assert not Administrador.objects.filter(usuario=empleado.usuario).exists()

    def test_crear_administrador_genera_usuario_y_perfil(self):
        """Crea conjuntamente la credencial y el perfil de administrador."""

        administrador = self.servicio.crear_administrador(
            "administrador1",
            "clave-segura-123",
        )

        assert administrador.usuario.check_password("clave-segura-123")
        assert not Empleado.objects.filter(usuario=administrador.usuario).exists()

    def test_superusuario_tecnico_tambien_es_administrador(self):
        """Mantiene la especialización total al crear un superusuario de Django."""

        usuario = Usuario.objects.create_superuser("superusuario", "clave-segura-123")

        assert usuario.is_superuser
        assert Administrador.objects.filter(usuario=usuario).exists()
