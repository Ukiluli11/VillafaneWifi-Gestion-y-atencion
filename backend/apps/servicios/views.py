"""Vistas REST para administrar planes y conexiones contratadas."""

from rest_framework import status, viewsets
from rest_framework.response import Response

from apps.usuarios.permisos_drf import AccionesApiMixin, PermisoAccionSistema
from apps.usuarios.politicas import AccionSistema

from .models import Plan, Servicio
from .serializadores import (
    SerializadorPlan,
    SerializadorServicioEscritura,
    SerializadorServicioLectura,
)
from .servicios import ServicioContrataciones, ServicioPlanes


class VistaPlanes(AccionesApiMixin, viewsets.ModelViewSet):
    """Expone la gestión del catálogo de planes requerida por RF-04."""

    queryset = Plan.objects.all()
    serializer_class = SerializadorPlan
    permission_classes = (PermisoAccionSistema,)
    acciones_por_operacion = {
        "list": AccionSistema.CONSULTAR_PLANES,
        "retrieve": AccionSistema.CONSULTAR_PLANES,
        "create": AccionSistema.GESTIONAR_PLANES,
        "update": AccionSistema.GESTIONAR_PLANES,
        "partial_update": AccionSistema.GESTIONAR_PLANES,
        "destroy": AccionSistema.GESTIONAR_PLANES,
    }

    def destroy(self, request, *args, **kwargs):
        """Inactiva el plan y conserva las contrataciones que lo utilizaron."""

        ServicioPlanes().dar_de_baja(self.get_object())
        return Response(status=status.HTTP_204_NO_CONTENT)


class VistaServicios(AccionesApiMixin, viewsets.ModelViewSet):
    """Expone la consulta y gestión independiente de conexiones."""

    permission_classes = (PermisoAccionSistema,)
    acciones_por_operacion = {
        "list": AccionSistema.CONSULTAR_SERVICIOS,
        "retrieve": AccionSistema.CONSULTAR_SERVICIOS,
        "create": AccionSistema.GESTIONAR_SERVICIOS,
        "update": AccionSistema.GESTIONAR_SERVICIOS,
        "partial_update": AccionSistema.GESTIONAR_SERVICIOS,
        "destroy": AccionSistema.GESTIONAR_SERVICIOS,
    }

    def get_queryset(self):
        """Lista conexiones y permite filtrarlas por identificador de cliente."""

        consulta = Servicio.objects.select_related("cliente", "plan").all()
        identificador_cliente = self.request.query_params.get("id_cliente")
        if identificador_cliente:
            consulta = consulta.filter(cliente_id=identificador_cliente)
        return consulta

    def get_serializer_class(self):
        """Usa una representación enriquecida para las operaciones de lectura."""

        if self.action in {"list", "retrieve"}:
            return SerializadorServicioLectura
        return SerializadorServicioEscritura

    def destroy(self, request, *args, **kwargs):
        """Inactiva la conexión sin eliminar su historia."""

        ServicioContrataciones().dar_de_baja(self.get_object())
        return Response(status=status.HTTP_204_NO_CONTENT)
