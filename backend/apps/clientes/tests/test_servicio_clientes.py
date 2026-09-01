"""Pruebas de los casos de uso de clientes, teléfonos y contrataciones."""

from decimal import Decimal

import pytest
from django.core.exceptions import ValidationError

from apps.clientes.casos_uso import CasoUsoAltaIntegralCliente
from apps.clientes.models import Cliente, TelefonoCliente
from apps.clientes.servicios import ServicioClientes
from apps.servicios.models import Plan, Servicio
from apps.servicios.servicios import ServicioPlanes


@pytest.mark.django_db
class TestGestionClientes:
    """Comprueba las reglas funcionales incluidas en RF-01, RF-02 y RF-03."""

    def setup_method(self):
        """Crea un plan activo y los servicios usados en cada escenario."""

        self.plan = ServicioPlanes().crear(
            {
                "nombre": "Plan 30 Megas",
                "velocidad_mbps": 30,
                "precio_vigente": Decimal("26000.00"),
            }
        )
        self.clientes = ServicioClientes()
        self.alta_integral = CasoUsoAltaIntegralCliente()

    def datos_cliente(self, documento="30123456", nombre="María López"):
        """Devuelve datos válidos reutilizables para un cliente de prueba."""

        return {
            "tipo_documento": Cliente.TipoDocumento.DNI,
            "numero_documento": documento,
            "nombre_razon_social": nombre,
            "tipo_cliente": Cliente.TipoCliente.PERSONA,
            "contacto_calle": "Belgrano",
            "contacto_numero": "1250",
            "contacto_localidad": "Formosa",
        }

    def datos_servicio(self, **cambios):
        """Devuelve una conexión válida vinculada al plan de la prueba."""

        datos = {
            "id_plan": self.plan.pk,
            "instalacion_calle": "Belgrano",
            "instalacion_numero": "1250",
            "instalacion_localidad": "Formosa",
            "dia_vencimiento": 12,
            "ip": "172.138.1.10",
            "mac": "AA-BB-CC-DD-EE-10",
        }
        datos.update(cambios)
        return datos

    def crear_cliente_completo(self):
        """Ejecuta el alta integral estándar utilizada por varias pruebas."""

        return self.alta_integral.ejecutar(
            self.datos_cliente(),
            ["3704 123456", "+54 9 3704 654321"],
            [self.datos_servicio()],
        )

    def test_alta_integral_crea_cliente_telefonos_y_servicio(self):
        """Registra toda la información obligatoria de RF-01 en una transacción."""

        cliente = self.crear_cliente_completo()

        assert cliente.telefonos.count() == 2
        assert cliente.servicios.count() == 1
        assert cliente.servicios.get().plan == self.plan
        assert cliente.servicios.get().mac == "AA:BB:CC:DD:EE:10"

    def test_un_cliente_puede_contratar_mas_de_un_servicio(self):
        """Respeta la cardinalidad uno a muchos acordada para Cliente y Servicio."""

        segundo_plan = ServicioPlanes().crear(
            {
                "nombre": "Plan 50 Megas",
                "velocidad_mbps": 50,
                "precio_vigente": Decimal("35000.00"),
            }
        )
        servicios = [
            self.datos_servicio(),
            self.datos_servicio(
                id_plan=segundo_plan.pk,
                ip="172.138.1.11",
                mac="AA:BB:CC:DD:EE:11",
            ),
        ]

        cliente = self.alta_integral.ejecutar(
            self.datos_cliente(),
            ["3704123456"],
            servicios,
        )

        assert cliente.servicios.count() == 2

    def test_error_en_conexion_revierte_el_alta_completa(self):
        """Evita clientes incompletos cuando la MAC de su conexión es inválida."""

        with pytest.raises(ValidationError):
            self.alta_integral.ejecutar(
                self.datos_cliente(),
                ["3704123456"],
                [self.datos_servicio(mac="MAC-INVALIDA")],
            )

        assert Cliente.objects.count() == 0
        assert TelefonoCliente.objects.count() == 0

    def test_documento_normalizado_no_puede_duplicarse(self):
        """Detecta como iguales un DNI escrito con o sin separadores."""

        self.crear_cliente_completo()

        with pytest.raises(ValidationError):
            self.clientes.crear(self.datos_cliente("30.123.456", "Otra persona"), ["3704999999"])

    def test_edicion_reemplaza_datos_y_telefonos(self):
        """Actualiza los datos solicitados sin crear un cliente nuevo."""

        cliente = self.crear_cliente_completo()

        actualizado = self.clientes.actualizar(
            cliente,
            {"nombre_razon_social": "María López Actualizada"},
            ["3704-777777"],
        )

        assert actualizado.nombre_razon_social == "María López Actualizada"
        assert list(actualizado.telefonos.values_list("numero", flat=True)) == ["3704777777"]

    def test_baja_logica_inactiva_cliente_y_servicios(self):
        """Conserva los registros históricos pero impide que sigan activos."""

        cliente = self.crear_cliente_completo()

        self.clientes.dar_de_baja(cliente)

        cliente.refresh_from_db()
        assert cliente.estado == Cliente.Estado.INACTIVO
        assert cliente.servicios.get().estado == Servicio.Estado.INACTIVO

    @pytest.mark.parametrize(
        "termino",
        ["30123456", "María", "3704123456", "Formosa"],
    )
    def test_busqueda_encuentra_por_cada_criterio_de_rf03(self, termino):
        """Encuentra por documento, nombre, teléfono o localidad."""

        cliente = self.crear_cliente_completo()

        assert list(self.clientes.buscar(termino)) == [cliente]

    def test_no_permite_contratar_un_plan_inactivo(self):
        """Impide nuevas conexiones sobre planes dados de baja."""

        self.plan.estado = Plan.Estado.INACTIVO
        self.plan.save(update_fields=["estado"])

        with pytest.raises(ValidationError):
            self.alta_integral.ejecutar(
                self.datos_cliente(),
                ["3704123456"],
                [self.datos_servicio()],
            )
