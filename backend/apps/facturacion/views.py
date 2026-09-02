"""Vistas REST de cuotas y pagos correspondientes a RF-05 y RF-06."""

from rest_framework import mixins, status, viewsets
from rest_framework.decorators import action
from rest_framework.response import Response

from apps.usuarios.permisos_drf import AccionesApiMixin, PermisoAccionSistema
from apps.usuarios.politicas import AccionSistema

from .models import Cuota, Pago
from .serializadores import (
    SerializadorCuota,
    SerializadorPagoEscritura,
    SerializadorPagoLectura,
    SerializadorPeriodo,
)
from .servicios import ServicioFacturacion


class VistaCuotas(AccionesApiMixin, viewsets.ReadOnlyModelViewSet):
    """Expone cuotas e incorpora la generación mensual controlada."""

    serializer_class = SerializadorCuota
    permission_classes = (PermisoAccionSistema,)
    acciones_por_operacion = {
        "list": AccionSistema.CONSULTAR_CUENTAS,
        "retrieve": AccionSistema.CONSULTAR_CUENTAS,
        "generar": AccionSistema.GESTIONAR_CUENTAS,
    }

    def get_queryset(self):
        """Permite limitar el historial por cliente o servicio."""

        consulta = Cuota.objects.select_related("servicio__cliente", "pago").all()
        if self.request.query_params.get("id_cliente"):
            consulta = consulta.filter(servicio__cliente_id=self.request.query_params["id_cliente"])
        if self.request.query_params.get("id_servicio"):
            consulta = consulta.filter(servicio_id=self.request.query_params["id_servicio"])
        return consulta

    @action(detail=False, methods=("post",))
    def generar(self, request):
        """Genera una sola vez las cuotas faltantes del período recibido."""

        serializador = SerializadorPeriodo(data=request.data)
        serializador.is_valid(raise_exception=True)
        cantidad = ServicioFacturacion().generar_para_servicios_activos(
            serializador.validated_data["periodo"]
        )
        return Response({"cuotas_generadas": cantidad}, status=status.HTTP_201_CREATED)


class VistaPagos(
    AccionesApiMixin,
    mixins.CreateModelMixin,
    mixins.ListModelMixin,
    mixins.RetrieveModelMixin,
    viewsets.GenericViewSet,
):
    """Expone el historial y permite registrar pagos de cuotas completas."""

    permission_classes = (PermisoAccionSistema,)
    acciones_por_operacion = {
        "list": AccionSistema.CONSULTAR_PAGOS,
        "retrieve": AccionSistema.CONSULTAR_PAGOS,
        "create": AccionSistema.GESTIONAR_PAGOS,
    }

    def get_queryset(self):
        """Lista pagos y permite filtrarlos por cliente."""

        consulta = Pago.objects.select_related("cuenta").prefetch_related("cuotas__servicio")
        if self.request.query_params.get("id_cliente"):
            consulta = consulta.filter(
                cuotas__servicio__cliente_id=self.request.query_params["id_cliente"]
            ).distinct()
        return consulta

    def get_serializer_class(self):
        """Usa un contrato de escritura reducido y otro enriquecido para lectura."""

        if self.action == "create":
            return SerializadorPagoEscritura
        return SerializadorPagoLectura

    def create(self, request, *args, **kwargs):
        """Devuelve la representación completa del pago recién creado."""

        serializador = self.get_serializer(data=request.data)
        serializador.is_valid(raise_exception=True)
        pago = serializador.save()
        salida = SerializadorPagoLectura(pago, context=self.get_serializer_context())
        return Response(salida.data, status=status.HTTP_201_CREATED)
