"""Vistas REST para gestionar y buscar clientes."""

from rest_framework import status, viewsets
from rest_framework.response import Response

from apps.usuarios.permisos_drf import AccionesApiMixin, PermisoAccionSistema
from apps.usuarios.politicas import AccionSistema

from .serializadores import (
    SerializadorAltaCliente,
    SerializadorClienteLectura,
    SerializadorEdicionCliente,
)
from .servicios import ServicioClientes


class VistaClientes(AccionesApiMixin, viewsets.ModelViewSet):
    """Expone RF-01, RF-02 y RF-03 mediante una API autenticada."""

    permission_classes = (PermisoAccionSistema,)
    acciones_por_operacion = {
        "list": AccionSistema.CONSULTAR_CLIENTES,
        "retrieve": AccionSistema.CONSULTAR_CLIENTES,
        "create": AccionSistema.GESTIONAR_CLIENTES,
        "update": AccionSistema.GESTIONAR_CLIENTES,
        "partial_update": AccionSistema.GESTIONAR_CLIENTES,
        "destroy": AccionSistema.GESTIONAR_CLIENTES,
    }

    def get_queryset(self):
        """Lista o busca clientes de acuerdo con el parámetro `buscar`."""

        termino = self.request.query_params.get("buscar", "")
        return ServicioClientes().buscar(termino)

    def get_serializer_class(self):
        """Selecciona el contrato de entrada o salida adecuado a la operación."""

        if self.action == "create":
            return SerializadorAltaCliente
        if self.action in {"update", "partial_update"}:
            return SerializadorEdicionCliente
        return SerializadorClienteLectura

    def create(self, request, *args, **kwargs):
        """Registra y devuelve el alta integral con teléfonos y servicios."""

        serializador = self.get_serializer(data=request.data)
        serializador.is_valid(raise_exception=True)
        cliente = serializador.save()
        salida = SerializadorClienteLectura(cliente, context=self.get_serializer_context())
        return Response(salida.data, status=status.HTTP_201_CREATED)

    def update(self, request, *args, **kwargs):
        """Actualiza el cliente y devuelve su representación completa."""

        parcial = kwargs.pop("partial", False)
        cliente = self.get_object()
        serializador = self.get_serializer(cliente, data=request.data, partial=parcial)
        serializador.is_valid(raise_exception=True)
        cliente = serializador.save()
        salida = SerializadorClienteLectura(cliente, context=self.get_serializer_context())
        return Response(salida.data)

    def destroy(self, request, *args, **kwargs):
        """Ejecuta una baja lógica y conserva todas las relaciones históricas."""

        cliente = self.get_object()
        ServicioClientes().dar_de_baja(cliente)
        return Response(status=status.HTTP_204_NO_CONTENT)
