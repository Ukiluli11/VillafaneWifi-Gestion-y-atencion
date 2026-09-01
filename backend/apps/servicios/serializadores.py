"""Serializadores de la API para planes y servicios contratados."""

from django.core.exceptions import ValidationError as ErrorValidacionDominio
from rest_framework import serializers

from apps.clientes.models import Cliente

from .models import Plan, Servicio
from .servicios import ServicioContrataciones, ServicioPlanes


def convertir_error_dominio(error: ErrorValidacionDominio) -> serializers.ValidationError:
    """Traduce una validación del dominio a una respuesta correcta de la API."""

    detalle = getattr(error, "message_dict", None) or {"detalle": error.messages}
    return serializers.ValidationError(detalle)


class SerializadorPlan(serializers.ModelSerializer):
    """Valida y representa los datos comerciales de un plan."""

    class Meta:
        """Declara los campos públicos de la entidad Plan."""

        model = Plan
        fields = ("id", "nombre", "velocidad_mbps", "precio_vigente", "estado")
        read_only_fields = ("id",)

    def create(self, validated_data):
        """Delega el alta del plan al servicio de aplicación."""

        try:
            return ServicioPlanes().crear(validated_data)
        except ErrorValidacionDominio as error:
            raise convertir_error_dominio(error) from error

    def update(self, instance, validated_data):
        """Delega la modificación del plan al servicio de aplicación."""

        try:
            return ServicioPlanes().actualizar(instance, validated_data)
        except ErrorValidacionDominio as error:
            raise convertir_error_dominio(error) from error


class SerializadorServicioLectura(serializers.ModelSerializer):
    """Representa una conexión junto con la información de su plan."""

    plan = SerializadorPlan(read_only=True)
    id_cliente = serializers.IntegerField(source="cliente_id", read_only=True)

    class Meta:
        """Declara los datos expuestos al consultar servicios."""

        model = Servicio
        fields = (
            "id",
            "id_cliente",
            "plan",
            "instalacion_calle",
            "instalacion_numero",
            "instalacion_localidad",
            "dia_vencimiento",
            "fecha_alta",
            "ip",
            "mac",
            "estado",
        )


class SerializadorServicioEscritura(serializers.ModelSerializer):
    """Valida las altas y modificaciones de conexiones contratadas."""

    id_cliente = serializers.PrimaryKeyRelatedField(
        source="cliente",
        queryset=Cliente.objects.all(),
    )
    id_plan = serializers.PrimaryKeyRelatedField(source="plan", queryset=Plan.objects.all())

    class Meta:
        """Declara los campos admitidos al escribir un servicio."""

        model = Servicio
        fields = (
            "id",
            "id_cliente",
            "id_plan",
            "instalacion_calle",
            "instalacion_numero",
            "instalacion_localidad",
            "dia_vencimiento",
            "fecha_alta",
            "ip",
            "mac",
            "estado",
        )
        read_only_fields = ("id",)

    def create(self, validated_data):
        """Delega la contratación a su servicio de aplicación."""

        cliente = validated_data.pop("cliente")
        try:
            return ServicioContrataciones().crear(cliente, validated_data)
        except ErrorValidacionDominio as error:
            raise convertir_error_dominio(error) from error

    def update(self, instance, validated_data):
        """Delega la edición técnica y comercial de la conexión."""

        validated_data.pop("cliente", None)
        try:
            return ServicioContrataciones().actualizar(instance, validated_data)
        except ErrorValidacionDominio as error:
            raise convertir_error_dominio(error) from error
