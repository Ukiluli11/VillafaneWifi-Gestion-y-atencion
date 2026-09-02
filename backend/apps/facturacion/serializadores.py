"""Serializadores REST de cuotas, pagos y cuentas receptoras."""

from django.core.exceptions import ValidationError as ErrorValidacionDominio
from django.utils import timezone
from rest_framework import serializers

from .models import CuentaReceptora, Cuota, Pago
from .servicios import ServicioFacturacion


def convertir_error_dominio(error: ErrorValidacionDominio) -> serializers.ValidationError:
    """Convierte una validación de negocio en una respuesta REST legible."""

    detalle = getattr(error, "message_dict", None) or {"detalle": error.messages}
    return serializers.ValidationError(detalle)


class SerializadorCuentaReceptora(serializers.ModelSerializer):
    """Representa la cuenta en la que se acreditó una cobranza."""

    class Meta:
        """Declara los datos públicos de la cuenta receptora."""

        model = CuentaReceptora
        fields = ("id", "nombre", "tipo", "identificador", "estado")


class SerializadorCuota(serializers.ModelSerializer):
    """Expone una cuota junto con su estado derivado."""

    id_servicio = serializers.IntegerField(source="servicio_id", read_only=True)
    id_cliente = serializers.IntegerField(source="servicio.cliente_id", read_only=True)
    id_pago = serializers.IntegerField(source="pago_id", read_only=True)
    estado = serializers.CharField(source="estado_calculado", read_only=True)

    class Meta:
        """Declara los campos de consulta de una cuota mensual."""

        model = Cuota
        fields = (
            "id",
            "id_servicio",
            "id_cliente",
            "id_pago",
            "periodo",
            "monto",
            "fecha_emision",
            "fecha_vencimiento",
            "estado",
        )


class SerializadorPagoLectura(serializers.ModelSerializer):
    """Representa un pago y las cuotas completas que canceló."""

    cuenta = SerializadorCuentaReceptora(read_only=True)
    cuotas = SerializadorCuota(many=True, read_only=True)

    class Meta:
        """Declara los datos devueltos en el historial de pagos."""

        model = Pago
        fields = ("id", "fecha", "monto_total", "medio_pago", "cuenta", "cuotas")


class SerializadorPagoEscritura(serializers.Serializer):
    """Valida la selección de cuotas y registra una cobranza atómica."""

    ids_cuotas = serializers.PrimaryKeyRelatedField(
        source="cuotas",
        many=True,
        queryset=Cuota.objects.all(),
    )
    id_cuenta = serializers.PrimaryKeyRelatedField(
        source="cuenta",
        queryset=CuentaReceptora.objects.all(),
    )
    medio_pago = serializers.ChoiceField(choices=Pago.Medio.choices)
    fecha = serializers.DateTimeField(default=timezone.now)

    def create(self, validated_data):
        """Delega el pago al servicio que protege las reglas de integridad."""

        try:
            return ServicioFacturacion().registrar_pago(**validated_data)
        except ErrorValidacionDominio as error:
            raise convertir_error_dominio(error) from error


class SerializadorPeriodo(serializers.Serializer):
    """Valida el período solicitado para la facturación en bloque."""

    periodo = serializers.RegexField(regex=r"^\d{4}-(0[1-9]|1[0-2])$")
