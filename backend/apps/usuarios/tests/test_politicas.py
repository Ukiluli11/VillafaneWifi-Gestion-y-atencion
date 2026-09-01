"""Pruebas de la matriz de accesos funcionales definida para RF-30."""

import pytest

from apps.usuarios.models import Administrador, Empleado, Usuario
from apps.usuarios.politicas import AccionSistema, ServicioAutorizacion
from apps.usuarios.servicios import ServicioUsuarios


@pytest.mark.django_db
class TestServicioAutorizacion:
    """Comprueba los permisos de cada subtipo y área sin utilizar roles."""

    def setup_method(self):
        """Prepara los servicios empleados por cada escenario de prueba."""

        self.usuarios = ServicioUsuarios()
        self.autorizacion = ServicioAutorizacion()

    def test_administrador_puede_realizar_todas_las_acciones(self):
        """Concede al administrador la totalidad del catálogo funcional."""

        administrador = self.usuarios.crear_administrador("admin", "clave-segura-123")

        assert self.autorizacion.acciones_permitidas(administrador.usuario) == frozenset(
            AccionSistema
        )

    def test_administracion_gestiona_operaciones_pero_no_soporte(self):
        """Aplica la separación entre tareas administrativas y de soporte."""

        empleado = self.usuarios.crear_empleado(
            "administracion",
            "clave-segura-123",
            Empleado.Area.ADMINISTRACION,
        )

        assert self.autorizacion.puede(empleado.usuario, AccionSistema.GESTIONAR_PAGOS)
        assert self.autorizacion.puede(empleado.usuario, AccionSistema.CONSULTAR_REPORTES)
        assert not self.autorizacion.puede(
            empleado.usuario,
            AccionSistema.ATENDER_CONVERSACIONES,
        )
        assert not self.autorizacion.puede(
            empleado.usuario,
            AccionSistema.GESTIONAR_USUARIOS,
        )

    def test_soporte_consulta_servicios_y_gestiona_tickets(self):
        """Permite el trabajo técnico sin habilitar operaciones financieras."""

        empleado = self.usuarios.crear_empleado(
            "soporte",
            "clave-segura-123",
            Empleado.Area.SOPORTE,
        )

        assert self.autorizacion.puede(empleado.usuario, AccionSistema.CONSULTAR_SERVICIOS)
        assert self.autorizacion.puede(empleado.usuario, AccionSistema.GESTIONAR_TICKETS)
        assert not self.autorizacion.puede(empleado.usuario, AccionSistema.GESTIONAR_PAGOS)

    def test_atencion_consulta_cuentas_y_registra_tickets(self):
        """Habilita la atención inicial sin permitir cerrar tickets ni pagos."""

        empleado = self.usuarios.crear_empleado(
            "atencion",
            "clave-segura-123",
            Empleado.Area.ATENCION_CLIENTE,
        )

        assert self.autorizacion.puede(empleado.usuario, AccionSistema.CONSULTAR_CUENTAS)
        assert self.autorizacion.puede(empleado.usuario, AccionSistema.REGISTRAR_TICKETS)
        assert not self.autorizacion.puede(empleado.usuario, AccionSistema.GESTIONAR_TICKETS)
        assert not self.autorizacion.puede(empleado.usuario, AccionSistema.GESTIONAR_CUENTAS)

    def test_usuario_inactivo_no_posee_permisos(self):
        """Deniega toda acción aunque el usuario conserve su perfil asociado."""

        empleado = self.usuarios.crear_empleado(
            "inactivo",
            "clave-segura-123",
            Empleado.Area.SOPORTE,
        )
        empleado.usuario.is_active = False
        empleado.usuario.save(update_fields=["is_active"])

        assert not self.autorizacion.puede(
            empleado.usuario,
            AccionSistema.CONSULTAR_CLIENTES,
        )

    def test_usuario_sin_subtipo_no_posee_permisos(self):
        """Hace cumplir la participación total frente a datos incompletos."""

        usuario = Usuario.objects.create_user("sin_perfil", password="clave-segura-123")

        assert self.autorizacion.acciones_permitidas(usuario) == frozenset()

    def test_usuario_con_dos_subtipos_no_posee_permisos(self):
        """Hace cumplir la disyunción aun ante una inconsistencia en la base."""

        empleado = self.usuarios.crear_empleado(
            "doble_perfil",
            "clave-segura-123",
            Empleado.Area.SOPORTE,
        )
        Administrador.objects.create(usuario=empleado.usuario)

        assert self.autorizacion.acciones_permitidas(empleado.usuario) == frozenset()
