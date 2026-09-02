"""Pruebas de las pantallas de cuenta corriente y cobranza."""

from datetime import date
from decimal import Decimal

import pytest
from django.urls import reverse

from apps.clientes.servicios import ServicioClientes
from apps.facturacion.models import CuentaReceptora, Pago
from apps.facturacion.servicios import ServicioFacturacion
from apps.servicios.models import Servicio
from apps.servicios.servicios import ServicioContrataciones, ServicioPlanes
from apps.usuarios.models import Empleado
from apps.usuarios.servicios import ServicioUsuarios


@pytest.mark.django_db
class TestPanelFacturacion:
    """Comprueba recorridos y permisos del panel para RF-05 y RF-06."""

    def setup_method(self):
        """Prepara una cuenta corriente pendiente para cada prueba."""

        self.usuarios = ServicioUsuarios()
        self.cliente = ServicioClientes().crear(
            {
                "tipo_documento": "DNI",
                "numero_documento": "32123456",
                "nombre_razon_social": "Cliente Panel Cuenta",
                "tipo_cliente": "PERSONA",
                "contacto_calle": "Rivadavia",
                "contacto_numero": "10",
                "contacto_localidad": "Formosa",
            },
            ["3704111222"],
        )
        plan = ServicioPlanes().crear(
            {
                "nombre": "Plan Panel Cuenta",
                "velocidad_mbps": 30,
                "precio_vigente": Decimal("24000.00"),
            }
        )
        servicio = ServicioContrataciones().crear(
            self.cliente,
            {
                "plan": plan,
                "instalacion_calle": "Rivadavia",
                "instalacion_numero": "10",
                "instalacion_localidad": "Formosa",
                "dia_vencimiento": 10,
                "fecha_alta": date(2026, 1, 1),
                "estado": Servicio.Estado.ACTIVO,
            },
        )
        self.cuota, _ = ServicioFacturacion().generar_cuota(servicio, "2026-09")
        self.cuenta = CuentaReceptora.objects.create(
            nombre="Caja administración",
            tipo=CuentaReceptora.Tipo.CAJA,
            identificador="CAJA-1",
        )

    def test_administracion_consulta_y_registra_pago(self, client):
        """Cancela desde el panel una cuota completa del cliente."""

        empleado = self.usuarios.crear_empleado(
            "administracion_facturacion",
            "clave-segura-123",
            Empleado.Area.ADMINISTRACION,
        )
        client.force_login(empleado.usuario)

        detalle = client.get(reverse("facturacion-panel:detalle", args=[self.cliente.pk]))
        respuesta = client.post(
            reverse("facturacion-panel:registrar-pago", args=[self.cliente.pk]),
            {
                "cuotas": [self.cuota.pk],
                "cuenta": self.cuenta.pk,
                "medio_pago": Pago.Medio.EFECTIVO,
                "fecha": "2026-09-01T10:00",
            },
        )

        self.cuota.refresh_from_db()
        assert detalle.status_code == 200
        assert respuesta.status_code == 302
        assert self.cuota.pago is not None

    def test_atencion_consulta_pero_no_registra_pagos(self, client):
        """Aplica la matriz de permisos acordada para atención al cliente."""

        empleado = self.usuarios.crear_empleado(
            "atencion_facturacion",
            "clave-segura-123",
            Empleado.Area.ATENCION_CLIENTE,
        )
        client.force_login(empleado.usuario)

        detalle = client.get(reverse("facturacion-panel:detalle", args=[self.cliente.pk]))
        pago = client.get(reverse("facturacion-panel:registrar-pago", args=[self.cliente.pk]))

        assert detalle.status_code == 200
        assert pago.status_code == 403


@pytest.mark.django_db
def test_api_pago_expone_cuotas_canceladas(client):
    """Registra una cobranza y devuelve sus cuotas mediante la API."""

    usuarios = ServicioUsuarios()
    empleado = usuarios.crear_empleado(
        "administracion_api_facturacion",
        "clave-segura-123",
        Empleado.Area.ADMINISTRACION,
    )
    cliente = ServicioClientes().crear(
        {
            "tipo_documento": "DNI",
            "numero_documento": "29123456",
            "nombre_razon_social": "Cliente API Pago",
            "tipo_cliente": "PERSONA",
            "contacto_calle": "Junín",
            "contacto_numero": "44",
            "contacto_localidad": "Formosa",
        },
        ["3704222333"],
    )
    plan = ServicioPlanes().crear(
        {"nombre": "Plan API Pago", "velocidad_mbps": 20, "precio_vigente": 18000}
    )
    servicio = ServicioContrataciones().crear(
        cliente,
        {
            "plan": plan,
            "instalacion_calle": "Junín",
            "instalacion_numero": "44",
            "instalacion_localidad": "Formosa",
            "dia_vencimiento": 5,
            "fecha_alta": date(2026, 1, 1),
            "estado": Servicio.Estado.ACTIVO,
        },
    )
    cuota, _ = ServicioFacturacion().generar_cuota(servicio, "2026-09")
    cuenta = CuentaReceptora.objects.create(
        nombre="Mercado Pago",
        tipo=CuentaReceptora.Tipo.BILLETERA,
        identificador="villafane.mp",
    )
    client.force_login(empleado.usuario)

    respuesta = client.post(
        "/api/pagos/",
        {
            "ids_cuotas": [cuota.pk],
            "id_cuenta": cuenta.pk,
            "medio_pago": Pago.Medio.MERCADO_PAGO,
        },
        content_type="application/json",
    )

    assert respuesta.status_code == 201
    assert respuesta.json()["monto_total"] == "18000.00"
    assert respuesta.json()["cuotas"][0]["id"] == cuota.pk
