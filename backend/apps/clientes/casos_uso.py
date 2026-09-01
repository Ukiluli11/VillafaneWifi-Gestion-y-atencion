"""Casos de uso que coordinan clientes con otros módulos del sistema."""

from django.core.exceptions import ValidationError
from django.db import transaction

from apps.servicios.models import Plan
from apps.servicios.servicios import ServicioContrataciones

from .models import Cliente
from .servicios import ServicioClientes


class CasoUsoAltaIntegralCliente:
    """Coordina el alta atómica del cliente, sus teléfonos y sus conexiones."""

    def __init__(self):
        """Construye los servicios de dominio requeridos por el caso de uso."""

        self.servicio_clientes = ServicioClientes()
        self.servicio_contrataciones = ServicioContrataciones()

    @transaction.atomic
    def ejecutar(
        self,
        datos_cliente: dict,
        telefonos: list[str],
        datos_servicios: list[dict],
    ) -> Cliente:
        """Crea toda el alta o revierte la operación si algún dato resulta inválido."""

        if not datos_servicios:
            raise ValidationError("Debe contratar al menos un servicio para el nuevo cliente.")

        cliente = self.servicio_clientes.crear(datos_cliente, telefonos)
        for datos in datos_servicios:
            datos_contratacion = dict(datos)
            identificador_plan = datos_contratacion.pop("id_plan")
            try:
                plan = Plan.objects.get(pk=identificador_plan)
            except Plan.DoesNotExist as error:
                raise ValidationError("El plan seleccionado no existe.") from error
            self.servicio_contrataciones.crear(
                cliente,
                {**datos_contratacion, "plan": plan},
            )
        return cliente
