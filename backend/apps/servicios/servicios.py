"""Servicios de aplicación para administrar planes y conexiones contratadas."""

from django.core.exceptions import ValidationError
from django.db import transaction

from apps.clientes.models import Cliente

from .models import Plan, Servicio


def normalizar_mac(mac: str | None) -> str | None:
    """Convierte una dirección MAC válida al formato hexadecimal con dos puntos."""

    if not mac:
        return None
    return mac.strip().replace("-", ":").upper()


class ServicioPlanes:
    """Centraliza las altas, modificaciones y bajas lógicas de planes."""

    @transaction.atomic
    def crear(self, datos: dict) -> Plan:
        """Crea un plan luego de ejecutar todas sus validaciones de dominio."""

        datos_limpios = {**datos, "nombre": str(datos.get("nombre") or "").strip()}
        plan = Plan(**datos_limpios)
        plan.full_clean()
        plan.save()
        return plan

    @transaction.atomic
    def actualizar(self, plan: Plan, datos: dict) -> Plan:
        """Modifica únicamente los campos comerciales admitidos para un plan."""

        campos_editables = {"nombre", "velocidad_mbps", "precio_vigente", "estado"}
        for campo, valor in datos.items():
            if campo in campos_editables:
                setattr(plan, campo, str(valor or "").strip() if campo == "nombre" else valor)
        plan.full_clean()
        plan.save()
        return plan

    @transaction.atomic
    def dar_de_baja(self, plan: Plan) -> Plan:
        """Inactiva el plan sin afectar los servicios históricos asociados."""

        plan.estado = Plan.Estado.INACTIVO
        plan.save(update_fields=["estado"])
        return plan


class ServicioContrataciones:
    """Gestiona las conexiones que vinculan clientes con planes."""

    @transaction.atomic
    def crear(self, cliente: Cliente, datos: dict) -> Servicio:
        """Registra una conexión para un cliente y un plan activos."""

        if cliente.estado != Cliente.Estado.ACTIVO:
            raise ValidationError("No se puede contratar un servicio para un cliente inactivo.")

        datos_limpios = dict(datos)
        plan = datos_limpios.pop("plan")
        if plan.estado != Plan.Estado.ACTIVO:
            raise ValidationError("El plan seleccionado se encuentra inactivo.")
        datos_limpios["ip"] = datos_limpios.get("ip") or None
        datos_limpios["mac"] = normalizar_mac(datos_limpios.get("mac"))

        servicio = Servicio(cliente=cliente, plan=plan, **datos_limpios)
        servicio.full_clean()
        servicio.save()
        return servicio

    @transaction.atomic
    def actualizar(self, servicio: Servicio, datos: dict) -> Servicio:
        """Actualiza los datos técnicos o comerciales de una conexión."""

        campos_editables = {
            "plan",
            "instalacion_calle",
            "instalacion_numero",
            "instalacion_localidad",
            "dia_vencimiento",
            "fecha_alta",
            "ip",
            "mac",
            "estado",
        }
        for campo, valor in datos.items():
            if campo in campos_editables:
                setattr(servicio, campo, valor)
        servicio.ip = servicio.ip or None
        servicio.mac = normalizar_mac(servicio.mac)
        if servicio.plan.estado != Plan.Estado.ACTIVO and servicio.estado == Servicio.Estado.ACTIVO:
            raise ValidationError("Un servicio activo no puede asignarse a un plan inactivo.")
        servicio.full_clean()
        servicio.save()
        return servicio

    @transaction.atomic
    def dar_de_baja(self, servicio: Servicio) -> Servicio:
        """Inactiva una conexión sin eliminar su historial."""

        servicio.estado = Servicio.Estado.INACTIVO
        servicio.save(update_fields=["estado"])
        return servicio

    def dar_de_baja_por_cliente(self, cliente: Cliente) -> int:
        """Inactiva todas las conexiones vigentes del cliente y devuelve la cantidad."""

        return Servicio.objects.filter(cliente=cliente).exclude(
            estado=Servicio.Estado.INACTIVO
        ).update(estado=Servicio.Estado.INACTIVO)
