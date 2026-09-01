"""Pruebas de las pantallas de planes y conexiones."""

from decimal import Decimal

import pytest
from django.urls import reverse

from apps.clientes.servicios import ServicioClientes
from apps.servicios.models import Plan, Servicio
from apps.servicios.servicios import ServicioPlanes
from apps.usuarios.models import Empleado
from apps.usuarios.servicios import ServicioUsuarios


@pytest.mark.django_db
class TestPanelServicios:
    """Comprueba recorridos visuales y permisos de planes y servicios."""

    def setup_method(self):
        """Prepara usuarios y datos comerciales para las pruebas."""

        self.usuarios = ServicioUsuarios()
        self.plan = ServicioPlanes().crear(
            {
                "nombre": "Plan inicial panel",
                "velocidad_mbps": 20,
                "precio_vigente": Decimal("22000.00"),
            }
        )

    def autenticar_administracion(self, client):
        """Inicia sesión con permisos comerciales completos."""

        empleado = self.usuarios.crear_empleado(
            "administracion_panel_servicios",
            "clave-segura-123",
            Empleado.Area.ADMINISTRACION,
        )
        client.force_login(empleado.usuario)

    def test_catalogo_permite_crear_editar_e_inactivar_plan(self, client):
        """Recorre las operaciones visuales principales de RF-04."""

        self.autenticar_administracion(client)
        alta = client.post(
            reverse("servicios-panel:alta-plan"),
            {
                "nombre": "Plan 80 Panel",
                "velocidad_mbps": 80,
                "precio_vigente": "40000.00",
                "estado": Plan.Estado.ACTIVO,
            },
        )
        plan = Plan.objects.get(nombre="Plan 80 Panel")
        edicion = client.post(
            reverse("servicios-panel:editar-plan", args=[plan.pk]),
            {
                "nombre": "Plan 80 Actualizado",
                "velocidad_mbps": 80,
                "precio_vigente": "42000.00",
                "estado": Plan.Estado.ACTIVO,
            },
        )
        baja = client.post(reverse("servicios-panel:baja-plan", args=[plan.pk]))

        plan.refresh_from_db()
        assert alta.status_code == 302
        assert edicion.status_code == 302
        assert baja.status_code == 302
        assert plan.nombre == "Plan 80 Actualizado"
        assert plan.estado == Plan.Estado.INACTIVO

    def test_panel_permite_agregar_otra_conexion_al_cliente(self, client):
        """Registra una conexión adicional desde su formulario web."""

        self.autenticar_administracion(client)
        cliente = ServicioClientes().crear(
            {
                "tipo_documento": "DNI",
                "numero_documento": "33111222",
                "nombre_razon_social": "Cliente Conexión",
                "tipo_cliente": "PERSONA",
                "contacto_calle": "Mitre",
                "contacto_numero": "90",
                "contacto_localidad": "Formosa",
            },
            ["3704555444"],
        )

        respuesta = client.post(
            reverse("servicios-panel:alta-servicio"),
            {
                "cliente": cliente.pk,
                "plan": self.plan.pk,
                "instalacion_calle": "Mitre",
                "instalacion_numero": "90",
                "instalacion_localidad": "Formosa",
                "dia_vencimiento": 10,
                "fecha_alta": "2026-09-01",
                "ip": "172.138.1.40",
                "mac": "AA:BB:CC:DD:EE:40",
                "estado": Servicio.Estado.ACTIVO,
            },
        )

        assert respuesta.status_code == 302
        assert cliente.servicios.count() == 1

    def test_soporte_ve_conexiones_pero_no_el_catalogo_comercial(self, client):
        """Presenta solamente las secciones autorizadas para soporte."""

        empleado = self.usuarios.crear_empleado(
            "soporte_panel_servicios",
            "clave-segura-123",
            Empleado.Area.SOPORTE,
        )
        client.force_login(empleado.usuario)

        conexiones = client.get(reverse("servicios-panel:servicios"))
        planes = client.get(reverse("servicios-panel:planes"))

        assert conexiones.status_code == 200
        assert planes.status_code == 403
