"""Pruebas de las pantallas web para gestionar clientes."""

from decimal import Decimal

import pytest
from django.urls import reverse

from apps.clientes.models import Cliente
from apps.servicios.servicios import ServicioPlanes
from apps.usuarios.models import Empleado
from apps.usuarios.servicios import ServicioUsuarios


@pytest.mark.django_db
class TestPanelClientes:
    """Comprueba formularios, navegación y permisos de las vistas HTML."""

    def setup_method(self):
        """Crea el plan y los servicios auxiliares de cada escenario."""

        self.plan = ServicioPlanes().crear(
            {
                "nombre": "Plan Panel",
                "velocidad_mbps": 40,
                "precio_vigente": Decimal("30000.00"),
            }
        )
        self.usuarios = ServicioUsuarios()

    def datos_alta(self):
        """Devuelve los campos requeridos por el formulario visual de alta."""

        return {
            "tipo_documento": "DNI",
            "numero_documento": "42123123",
            "nombre_razon_social": "Cliente del Panel",
            "tipo_cliente": "PERSONA",
            "contacto_calle": "Rivadavia",
            "contacto_numero": "450",
            "contacto_localidad": "Formosa",
            "telefono_principal": "3704 321321",
            "telefono_alternativo": "",
            "plan": self.plan.pk,
            "instalacion_calle": "Rivadavia",
            "instalacion_numero": "450",
            "instalacion_localidad": "Formosa",
            "dia_vencimiento": 15,
            "ip": "172.138.1.30",
            "mac": "AA:BB:CC:DD:EE:30",
        }

    def autenticar_administracion(self, client):
        """Inicia una sesión con acceso de gestión de clientes."""

        empleado = self.usuarios.crear_empleado(
            "administracion_panel_clientes",
            "clave-segura-123",
            Empleado.Area.ADMINISTRACION,
        )
        client.force_login(empleado.usuario)
        return empleado

    def test_panel_muestra_listado_y_formulario_de_alta(self, client):
        """Renderiza las primeras pantallas navegables del módulo."""

        self.autenticar_administracion(client)

        listado = client.get(reverse("clientes-panel:lista"))
        formulario = client.get(reverse("clientes-panel:alta"))

        assert listado.status_code == 200
        assert "Clientes" in listado.content.decode()
        assert formulario.status_code == 200
        assert "Primera conexión" in formulario.content.decode()

    def test_alta_web_crea_y_muestra_la_ficha_completa(self, client):
        """Completa el recorrido visual desde el formulario hasta el detalle."""

        self.autenticar_administracion(client)

        respuesta = client.post(reverse("clientes-panel:alta"), self.datos_alta(), follow=True)

        cliente = Cliente.objects.get(numero_documento="42123123")
        assert respuesta.status_code == 200
        assert respuesta.redirect_chain[-1][0] == reverse(
            "clientes-panel:detalle",
            args=[cliente.pk],
        )
        assert "Cliente del Panel" in respuesta.content.decode()
        assert cliente.servicios.count() == 1

    def test_edicion_y_baja_funcionan_desde_el_panel(self, client):
        """Modifica contactos y luego ejecuta la baja lógica desde HTML."""

        self.autenticar_administracion(client)
        client.post(reverse("clientes-panel:alta"), self.datos_alta())
        cliente = Cliente.objects.get(numero_documento="42123123")
        datos_edicion = {
            "tipo_documento": cliente.tipo_documento,
            "numero_documento": cliente.numero_documento,
            "nombre_razon_social": "Cliente Editado",
            "tipo_cliente": cliente.tipo_cliente,
            "contacto_calle": cliente.contacto_calle,
            "contacto_numero": cliente.contacto_numero,
            "contacto_localidad": cliente.contacto_localidad,
            "telefonos": "3704 999888, 3704 777666",
        }

        edicion = client.post(reverse("clientes-panel:editar", args=[cliente.pk]), datos_edicion)
        baja = client.post(reverse("clientes-panel:baja", args=[cliente.pk]))

        cliente.refresh_from_db()
        assert edicion.status_code == 302
        assert baja.status_code == 302
        assert cliente.nombre_razon_social == "Cliente Editado"
        assert cliente.estado == Cliente.Estado.INACTIVO
        assert cliente.telefonos.count() == 2

    def test_soporte_consulta_clientes_pero_no_accede_al_alta(self, client):
        """Refleja en las pantallas la matriz de acceso de soporte."""

        empleado = self.usuarios.crear_empleado(
            "soporte_panel_clientes",
            "clave-segura-123",
            Empleado.Area.SOPORTE,
        )
        client.force_login(empleado.usuario)

        listado = client.get(reverse("clientes-panel:lista"))
        alta = client.get(reverse("clientes-panel:alta"))

        assert listado.status_code == 200
        assert alta.status_code == 403
