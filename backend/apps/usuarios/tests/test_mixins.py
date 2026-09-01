"""Pruebas de la protección reutilizable aplicada a vistas funcionales."""

import pytest
from django.http import HttpResponse
from django.urls import path
from django.views import View

from apps.usuarios.mixins import AccionRequeridaMixin
from apps.usuarios.models import Empleado
from apps.usuarios.politicas import AccionSistema
from apps.usuarios.servicios import ServicioUsuarios


class VistaGestionPagos(AccionRequeridaMixin, View):
    """Representa una vista mínima protegida para verificar el mixin."""

    accion_requerida = AccionSistema.GESTIONAR_PAGOS

    def get(self, request):
        """Devuelve una respuesta solo cuando la política concede acceso."""

        return HttpResponse("Acceso concedido")


urlpatterns = [path("prueba-pagos/", VistaGestionPagos.as_view(), name="prueba-pagos")]


@pytest.fixture(autouse=True)
def configurar_rutas_prueba(settings):
    """Utiliza las rutas mínimas de este módulo durante cada prueba."""

    settings.ROOT_URLCONF = __name__
    settings.LOGIN_URL = "/iniciar-sesion/"


@pytest.mark.django_db
class TestAccionRequeridaMixin:
    """Comprueba que la decisión de la política se aplique a una vista real."""

    def setup_method(self):
        """Construye el servicio que crea usuarios especializados."""

        self.usuarios = ServicioUsuarios()

    def test_empleado_autorizado_accede_a_la_vista(self, client):
        """Permite a administración ingresar a la gestión de pagos."""

        empleado = self.usuarios.crear_empleado(
            "administracion_vista",
            "clave-segura-123",
            Empleado.Area.ADMINISTRACION,
        )
        client.force_login(empleado.usuario)

        respuesta = client.get("/prueba-pagos/")

        assert respuesta.status_code == 200

    def test_empleado_no_autorizado_recibe_prohibicion(self, client):
        """Impide a soporte ingresar a una operación financiera."""

        empleado = self.usuarios.crear_empleado(
            "soporte_vista",
            "clave-segura-123",
            Empleado.Area.SOPORTE,
        )
        client.force_login(empleado.usuario)

        respuesta = client.get("/prueba-pagos/")

        assert respuesta.status_code == 403

    def test_visitante_es_redirigido_al_inicio_de_sesion(self, client):
        """Solicita autenticación antes de evaluar permisos funcionales."""

        respuesta = client.get("/prueba-pagos/")

        assert respuesta.status_code == 302
