"""Pruebas de las reglas de cuotas, pagos y cuenta corriente."""

from datetime import date
from decimal import Decimal

import pytest
from django.core.exceptions import ValidationError

from apps.clientes.servicios import ServicioClientes
from apps.facturacion.models import CuentaReceptora
from apps.facturacion.servicios import ServicioCuentaCorriente, ServicioFacturacion
from apps.servicios.models import Servicio
from apps.servicios.servicios import ServicioContrataciones, ServicioPlanes


@pytest.mark.django_db
class TestServiciosFacturacion:
    """Comprueba las decisiones de negocio acordadas para RF-05 y RF-06."""

    def setup_method(self):
        """Crea un cliente con dos conexiones y una cuenta de cobro."""

        self.cliente = ServicioClientes().crear(
            {
                "tipo_documento": "DNI",
                "numero_documento": "30111222",
                "nombre_razon_social": "Cliente Facturación",
                "tipo_cliente": "PERSONA",
                "contacto_calle": "Belgrano",
                "contacto_numero": "20",
                "contacto_localidad": "Formosa",
            },
            ["3704000011"],
        )
        self.plan = ServicioPlanes().crear(
            {
                "nombre": "Plan Facturación",
                "velocidad_mbps": 50,
                "precio_vigente": Decimal("26000.00"),
            }
        )
        datos = {
            "plan": self.plan,
            "instalacion_calle": "Belgrano",
            "instalacion_numero": "20",
            "instalacion_localidad": "Formosa",
            "dia_vencimiento": 31,
            "fecha_alta": date(2026, 1, 10),
            "estado": Servicio.Estado.ACTIVO,
        }
        self.servicio_uno = ServicioContrataciones().crear(self.cliente, datos)
        self.servicio_dos = ServicioContrataciones().crear(
            self.cliente,
            {**datos, "instalacion_numero": "22", "dia_vencimiento": 15},
        )
        self.cuenta = CuentaReceptora.objects.create(
            nombre="Banco Galicia",
            tipo=CuentaReceptora.Tipo.BANCO,
            identificador="villafane.wifi",
        )
        self.facturacion = ServicioFacturacion()

    def test_generacion_es_idempotente_y_congela_precio(self):
        """No duplica la cuota ni altera su monto ante un precio posterior."""

        cuota, creada = self.facturacion.generar_cuota(self.servicio_uno, "2026-02")
        self.plan.precio_vigente = Decimal("30000.00")
        self.plan.save()
        repetida, creada_nuevamente = self.facturacion.generar_cuota(
            self.servicio_uno,
            "2026-02",
        )

        assert creada is True
        assert creada_nuevamente is False
        assert repetida.pk == cuota.pk
        assert cuota.monto == Decimal("26000.00")
        assert cuota.fecha_vencimiento == date(2026, 2, 28)

    def test_un_pago_cancela_cuotas_completas_de_varios_servicios(self):
        """Agrupa varias cuotas del cliente en una única transferencia."""

        cuota_uno, _ = self.facturacion.generar_cuota(self.servicio_uno, "2026-08")
        cuota_dos, _ = self.facturacion.generar_cuota(self.servicio_dos, "2026-08")
        pago = self.facturacion.registrar_pago(
            [cuota_uno, cuota_dos],
            self.cuenta,
            "TRANSFERENCIA",
        )

        cuota_uno.refresh_from_db()
        cuota_dos.refresh_from_db()
        assert pago.monto_total == Decimal("52000.00")
        assert cuota_uno.pago == pago
        assert cuota_dos.pago == pago

    def test_rechaza_volver_a_pagar_una_cuota(self):
        """Evita que una cobranza ya imputada sea acreditada por duplicado."""

        cuota, _ = self.facturacion.generar_cuota(self.servicio_uno, "2026-07")
        self.facturacion.registrar_pago([cuota], self.cuenta, "EFECTIVO")
        cuota.refresh_from_db()

        with pytest.raises(ValidationError):
            self.facturacion.registrar_pago([cuota], self.cuenta, "EFECTIVO")

    def test_resumen_distingue_deuda_vencida_y_proximo_vencimiento(self):
        """Calcula el saldo sin almacenarlo como un atributo redundante."""

        self.facturacion.generar_cuota(self.servicio_uno, "2026-08")
        self.facturacion.generar_cuota(self.servicio_dos, "2026-09")
        resumen = ServicioCuentaCorriente().resumir(
            self.cliente,
            fecha_referencia=date(2026, 9, 1),
        )

        assert resumen.total_pendiente == Decimal("52000.00")
        assert resumen.total_vencido == Decimal("26000.00")
        assert resumen.proximo_vencimiento == date(2026, 9, 15)
        assert resumen.estado == "CON_DEUDA"
