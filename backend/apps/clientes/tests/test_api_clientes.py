"""Pruebas de integración de la API de clientes y su control de acceso."""

from decimal import Decimal

import pytest

from apps.clientes.models import Cliente
from apps.servicios.servicios import ServicioPlanes
from apps.usuarios.models import Empleado
from apps.usuarios.servicios import ServicioUsuarios


@pytest.mark.django_db
class TestApiClientes:
    """Comprueba operaciones HTTP y permisos funcionales de RF-01 a RF-03."""

    def setup_method(self):
        """Prepara un plan y el servicio de creación de usuarios internos."""

        self.plan = ServicioPlanes().crear(
            {
                "nombre": "Plan API",
                "velocidad_mbps": 30,
                "precio_vigente": Decimal("26000.00"),
            }
        )
        self.usuarios = ServicioUsuarios()

    def carga_alta(self):
        """Devuelve el cuerpo JSON válido de un alta integral."""

        return {
            "tipo_documento": "DNI",
            "numero_documento": "40111222",
            "nombre_razon_social": "Cliente API",
            "tipo_cliente": "PERSONA",
            "contacto_calle": "San Martín",
            "contacto_numero": "850",
            "contacto_localidad": "Formosa",
            "telefonos": ["3704 111222"],
            "servicios": [
                {
                    "id_plan": self.plan.pk,
                    "instalacion_calle": "San Martín",
                    "instalacion_numero": "850",
                    "instalacion_localidad": "Formosa",
                    "dia_vencimiento": 8,
                    "ip": "172.138.1.20",
                    "mac": "AA:BB:CC:DD:EE:20",
                }
            ],
        }

    def test_administracion_puede_realizar_alta_integral(self, client):
        """Permite crear un cliente completo al área administrativa."""

        empleado = self.usuarios.crear_empleado(
            "administracion_clientes",
            "clave-segura-123",
            Empleado.Area.ADMINISTRACION,
        )
        client.force_login(empleado.usuario)

        respuesta = client.post(
            "/api/clientes/",
            self.carga_alta(),
            content_type="application/json",
        )

        assert respuesta.status_code == 201
        assert respuesta.json()["telefonos"] == [{"numero": "3704111222"}]
        assert len(respuesta.json()["servicios"]) == 1

    def test_soporte_puede_consultar_pero_no_crear_clientes(self, client):
        """Aplica a la API la política establecida para soporte técnico."""

        empleado = self.usuarios.crear_empleado(
            "soporte_clientes",
            "clave-segura-123",
            Empleado.Area.SOPORTE,
        )
        client.force_login(empleado.usuario)

        consulta = client.get("/api/clientes/")
        alta = client.post("/api/clientes/", self.carga_alta(), content_type="application/json")

        assert consulta.status_code == 200
        assert alta.status_code == 403

    def test_busqueda_y_baja_logica_funcionan_desde_api(self, client):
        """Expone la búsqueda de RF-03 y la baja lógica de RF-02."""

        empleado = self.usuarios.crear_empleado(
            "administracion_busqueda",
            "clave-segura-123",
            Empleado.Area.ADMINISTRACION,
        )
        client.force_login(empleado.usuario)
        alta = client.post("/api/clientes/", self.carga_alta(), content_type="application/json")
        identificador = alta.json()["id"]

        busqueda = client.get("/api/clientes/?buscar=3704111222")
        baja = client.delete(f"/api/clientes/{identificador}/")

        assert len(busqueda.json()) == 1
        assert baja.status_code == 204
        assert Cliente.objects.get(pk=identificador).estado == Cliente.Estado.INACTIVO

    def test_edicion_parcial_actualiza_el_cliente(self, client):
        """Permite modificar solo los campos enviados mediante PATCH."""

        empleado = self.usuarios.crear_empleado(
            "administracion_edicion",
            "clave-segura-123",
            Empleado.Area.ADMINISTRACION,
        )
        client.force_login(empleado.usuario)
        alta = client.post(
            "/api/clientes/",
            self.carga_alta(),
            content_type="application/json",
        )

        respuesta = client.patch(
            f"/api/clientes/{alta.json()['id']}/",
            {"nombre_razon_social": "Cliente API Editado"},
            content_type="application/json",
        )

        assert respuesta.status_code == 200
        assert respuesta.json()["nombre_razon_social"] == "Cliente API Editado"

    def test_dato_tecnico_invalido_devuelve_error_y_revierte_alta(self, client):
        """Informa un error de validación sin conservar una carga incompleta."""

        empleado = self.usuarios.crear_empleado(
            "administracion_error",
            "clave-segura-123",
            Empleado.Area.ADMINISTRACION,
        )
        client.force_login(empleado.usuario)
        carga = self.carga_alta()
        carga["servicios"][0]["mac"] = "MAC-INVALIDA"

        respuesta = client.post("/api/clientes/", carga, content_type="application/json")

        assert respuesta.status_code == 400
        assert Cliente.objects.count() == 0

    def test_visitante_no_puede_consultar_clientes(self, client):
        """Impide acceder al módulo sin una sesión iniciada."""

        respuesta = client.get("/api/clientes/")

        assert respuesta.status_code in {401, 403}
