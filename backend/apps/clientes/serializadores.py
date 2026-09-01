"""Serializadores de la API para altas y consultas de clientes."""

from django.core.exceptions import ValidationError as ErrorValidacionDominio
from rest_framework import serializers

from apps.servicios.models import Plan
from apps.servicios.serializadores import SerializadorServicioLectura

from .casos_uso import CasoUsoAltaIntegralCliente
from .models import Cliente, TelefonoCliente
from .servicios import ServicioClientes
from .validadores import validar_telefono


def convertir_error_dominio(error: ErrorValidacionDominio) -> serializers.ValidationError:
    """Traduce un error de dominio al formato esperado por la API REST."""

    detalle = getattr(error, "message_dict", None) or {"detalle": error.messages}
    return serializers.ValidationError(detalle)


class SerializadorTelefonoCliente(serializers.ModelSerializer):
    """Representa un número de teléfono o WhatsApp del cliente."""

    class Meta:
        """Declara el único campo público del contacto."""

        model = TelefonoCliente
        fields = ("numero",)


class SerializadorAltaServicio(serializers.Serializer):
    """Valida una conexión incluida dentro del alta integral del cliente."""

    id_plan = serializers.PrimaryKeyRelatedField(queryset=Plan.objects.all())
    instalacion_calle = serializers.CharField(max_length=120)
    instalacion_numero = serializers.CharField(max_length=20, required=False, allow_blank=True)
    instalacion_localidad = serializers.CharField(max_length=100)
    dia_vencimiento = serializers.IntegerField(min_value=1, max_value=31)
    fecha_alta = serializers.DateField(required=False)
    ip = serializers.IPAddressField(required=False, allow_null=True, allow_blank=True)
    mac = serializers.CharField(max_length=17, required=False, allow_null=True, allow_blank=True)

    def to_internal_value(self, data):
        """Convierte el objeto Plan validado nuevamente a su identificador."""

        valores = super().to_internal_value(data)
        valores["id_plan"] = valores["id_plan"].pk
        return valores


class SerializadorClienteLectura(serializers.ModelSerializer):
    """Representa al cliente con todos sus teléfonos y conexiones."""

    telefonos = SerializadorTelefonoCliente(many=True, read_only=True)
    servicios = SerializadorServicioLectura(many=True, read_only=True)

    class Meta:
        """Declara la representación completa utilizada en consultas."""

        model = Cliente
        fields = (
            "id",
            "tipo_documento",
            "numero_documento",
            "nombre_razon_social",
            "tipo_cliente",
            "contacto_calle",
            "contacto_numero",
            "contacto_localidad",
            "estado",
            "telefonos",
            "servicios",
        )


class SerializadorAltaCliente(serializers.Serializer):
    """Valida el alta conjunta requerida por RF-01."""

    tipo_documento = serializers.ChoiceField(
        choices=Cliente.TipoDocumento.choices,
        default=Cliente.TipoDocumento.DNI,
    )
    numero_documento = serializers.CharField(max_length=30)
    nombre_razon_social = serializers.CharField(max_length=160)
    tipo_cliente = serializers.ChoiceField(
        choices=Cliente.TipoCliente.choices,
        default=Cliente.TipoCliente.PERSONA,
    )
    contacto_calle = serializers.CharField(max_length=120)
    contacto_numero = serializers.CharField(max_length=20, required=False, allow_blank=True)
    contacto_localidad = serializers.CharField(max_length=100)
    telefonos = serializers.ListField(child=serializers.CharField(max_length=30), min_length=1)
    servicios = SerializadorAltaServicio(many=True, allow_empty=False)

    def validate_telefonos(self, value):
        """Normaliza y valida todos los teléfonos recibidos."""

        try:
            return list(dict.fromkeys(validar_telefono(numero) for numero in value))
        except ErrorValidacionDominio as error:
            raise convertir_error_dominio(error) from error

    def create(self, validated_data):
        """Ejecuta el alta atómica del cliente y sus servicios."""

        telefonos = validated_data.pop("telefonos")
        datos_servicios = validated_data.pop("servicios")
        try:
            return CasoUsoAltaIntegralCliente().ejecutar(
                validated_data,
                telefonos,
                datos_servicios,
            )
        except ErrorValidacionDominio as error:
            raise convertir_error_dominio(error) from error


class SerializadorEdicionCliente(serializers.ModelSerializer):
    """Valida la modificación de datos y teléfonos requerida por RF-02."""

    telefonos = serializers.ListField(
        child=serializers.CharField(max_length=30),
        required=False,
        allow_empty=False,
        write_only=True,
    )

    class Meta:
        """Declara los campos modificables sin permitir una baja física."""

        model = Cliente
        fields = (
            "tipo_documento",
            "numero_documento",
            "nombre_razon_social",
            "tipo_cliente",
            "contacto_calle",
            "contacto_numero",
            "contacto_localidad",
            "telefonos",
        )

    def validate_telefonos(self, value):
        """Normaliza los teléfonos proporcionados durante una edición."""

        try:
            return list(dict.fromkeys(validar_telefono(numero) for numero in value))
        except ErrorValidacionDominio as error:
            raise convertir_error_dominio(error) from error

    def update(self, instance, validated_data):
        """Delega la modificación al servicio responsable del módulo."""

        telefonos = validated_data.pop("telefonos", None)
        try:
            return ServicioClientes().actualizar(instance, validated_data, telefonos)
        except ErrorValidacionDominio as error:
            raise convertir_error_dominio(error) from error
