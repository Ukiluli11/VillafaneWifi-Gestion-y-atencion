"""Pruebas de la API del catálogo de planes y servicios."""

import pytest

from apps.usuarios.models import Empleado
from apps.usuarios.servicios import ServicioUsuarios


@pytest.mark.django_db
class TestApiPlanes:
    """Comprueba la gestión y los permisos principales definidos para RF-04."""

    def setup_method(self):
        """Construye el servicio de usuarios internos para cada prueba."""

        self.usuarios = ServicioUsuarios()

    def test_administracion_puede_crear_e_inactivar_un_plan(self, client):
        """Gestiona el catálogo sin eliminar físicamente los planes."""

        empleado = self.usuarios.crear_empleado(
            "administracion_planes",
            "clave-segura-123",
            Empleado.Area.ADMINISTRACION,
        )
        client.force_login(empleado.usuario)

        alta = client.post(
            "/api/planes/",
            {"nombre": "Plan 100 Megas", "velocidad_mbps": 100, "precio_vigente": "45000"},
            content_type="application/json",
        )
        baja = client.delete(f"/api/planes/{alta.json()['id']}/")

        assert alta.status_code == 201
        assert baja.status_code == 204

    def test_soporte_no_puede_modificar_el_catalogo(self, client):
        """Impide al área técnica crear planes comerciales."""

        empleado = self.usuarios.crear_empleado(
            "soporte_planes",
            "clave-segura-123",
            Empleado.Area.SOPORTE,
        )
        client.force_login(empleado.usuario)

        respuesta = client.post(
            "/api/planes/",
            {"nombre": "Plan indebido", "velocidad_mbps": 10, "precio_vigente": "1000"},
            content_type="application/json",
        )

        assert respuesta.status_code == 403
