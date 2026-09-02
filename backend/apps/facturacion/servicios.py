"""Servicios de dominio para facturación, pagos y cuenta corriente."""

import calendar
from dataclasses import dataclass
from datetime import date, datetime
from decimal import Decimal

from django.core.exceptions import ValidationError
from django.db import transaction
from django.db.models import Q, QuerySet, Sum
from django.utils import timezone

from apps.clientes.models import Cliente
from apps.servicios.models import Servicio

from .models import CuentaReceptora, Cuota, Pago


@dataclass(frozen=True)
class ResumenCuentaCorriente:
    """Agrupa los valores calculados que resumen la situación de un cliente."""

    total_pendiente: Decimal
    total_vencido: Decimal
    cantidad_pendientes: int
    cantidad_vencidas: int
    proximo_vencimiento: date | None

    @property
    def estado(self) -> str:
        """Clasifica la cuenta según la existencia de deuda vencida o pendiente."""

        if self.total_vencido > 0:
            return "CON_DEUDA"
        if self.total_pendiente > 0:
            return "AL_DIA_CON_CUOTAS"
        return "AL_DIA"

    @property
    def estado_mostrado(self) -> str:
        """Traduce el estado técnico a una etiqueta visible en el panel."""

        etiquetas = {
            "CON_DEUDA": "Con deuda vencida",
            "AL_DIA_CON_CUOTAS": "Al día · pago pendiente",
            "AL_DIA": "Al día",
        }
        return etiquetas[self.estado]


class ServicioFacturacion:
    """Genera cuotas mensuales y registra pagos con reglas de negocio consistentes."""

    def calcular_fecha_vencimiento(self, periodo: str, dia_vencimiento: int) -> date:
        """Obtiene el vencimiento y ajusta días 29 a 31 al último día del mes."""

        try:
            anio, mes = (int(parte) for parte in periodo.split("-"))
            ultimo_dia = calendar.monthrange(anio, mes)[1]
        except (AttributeError, TypeError, ValueError) as error:
            raise ValidationError("El período debe utilizar el formato AAAA-MM.") from error
        return date(anio, mes, min(dia_vencimiento, ultimo_dia))

    @transaction.atomic
    def generar_cuota(self, servicio: Servicio, periodo: str) -> tuple[Cuota, bool]:
        """Genera una única cuota mensual y conserva el precio vigente como histórico."""

        if servicio.estado == Servicio.Estado.INACTIVO:
            raise ValidationError("No se pueden generar cuotas para un servicio inactivo.")

        fecha_vencimiento = self.calcular_fecha_vencimiento(
            periodo,
            servicio.dia_vencimiento,
        )
        fecha_emision = date(fecha_vencimiento.year, fecha_vencimiento.month, 1)
        if servicio.fecha_alta > fecha_vencimiento:
            raise ValidationError("El servicio todavía no estaba contratado en ese período.")
        if servicio.fecha_alta > fecha_emision:
            fecha_emision = servicio.fecha_alta
            fecha_vencimiento = max(fecha_vencimiento, fecha_emision)

        cuota, creada = Cuota.objects.get_or_create(
            servicio=servicio,
            periodo=periodo,
            defaults={
                "monto": servicio.plan.precio_vigente,
                "fecha_emision": fecha_emision,
                "fecha_vencimiento": fecha_vencimiento,
            },
        )
        return cuota, creada

    @transaction.atomic
    def generar_para_servicios_activos(self, periodo: str) -> int:
        """Crea las cuotas faltantes de todos los servicios activos para un período."""

        cantidad = 0
        servicios = Servicio.objects.filter(estado=Servicio.Estado.ACTIVO).select_related("plan")
        for servicio in servicios:
            try:
                _, creada = self.generar_cuota(servicio, periodo)
            except ValidationError as error:
                if "todavía no estaba contratado" in str(error):
                    continue
                raise
            cantidad += int(creada)
        return cantidad

    @transaction.atomic
    def registrar_pago(
        self,
        cuotas: QuerySet[Cuota] | list[Cuota],
        cuenta: CuentaReceptora,
        medio_pago: str,
        fecha: datetime | None = None,
    ) -> Pago:
        """Cancela cuotas completas de un cliente mediante un único pago atómico."""

        identificadores = [cuota.pk for cuota in cuotas]
        if not identificadores:
            raise ValidationError("Debe seleccionar al menos una cuota para registrar el pago.")
        if cuenta.estado != CuentaReceptora.Estado.ACTIVA:
            raise ValidationError("La cuenta receptora seleccionada se encuentra inactiva.")
        if medio_pago not in Pago.Medio.values:
            raise ValidationError("El medio de pago seleccionado no es válido.")

        cuotas_bloqueadas = list(
            Cuota.objects.select_for_update()
            .select_related("servicio__cliente")
            .filter(pk__in=identificadores)
        )
        if len(cuotas_bloqueadas) != len(set(identificadores)):
            raise ValidationError("Alguna de las cuotas seleccionadas ya no existe.")
        if any(cuota.pago_id for cuota in cuotas_bloqueadas):
            raise ValidationError("Alguna de las cuotas seleccionadas ya se encuentra pagada.")

        clientes = {cuota.servicio.cliente_id for cuota in cuotas_bloqueadas}
        if len(clientes) != 1:
            raise ValidationError("Un pago solo puede cancelar cuotas pertenecientes a un cliente.")

        monto_total = sum((cuota.monto for cuota in cuotas_bloqueadas), Decimal("0.00"))
        pago = Pago.objects.create(
            cuenta=cuenta,
            fecha=fecha or timezone.now(),
            monto_total=monto_total,
            medio_pago=medio_pago,
        )
        Cuota.objects.filter(pk__in=identificadores).update(pago=pago)
        return pago


class ServicioCuentasReceptoras:
    """Administra las cuentas habilitadas para acreditar cobranzas."""

    @transaction.atomic
    def crear(self, datos: dict) -> CuentaReceptora:
        """Crea una cuenta receptora después de validar su identidad."""

        cuenta = CuentaReceptora(**datos)
        cuenta.full_clean()
        cuenta.save()
        return cuenta


class ServicioCuentaCorriente:
    """Consulta la deuda y el historial sin duplicar saldos en la base de datos."""

    def cuotas_del_cliente(self, cliente: Cliente) -> QuerySet[Cuota]:
        """Devuelve todas las cuotas del cliente con relaciones precargadas."""

        return (
            Cuota.objects.filter(servicio__cliente=cliente)
            .select_related("servicio__plan", "pago__cuenta")
            .order_by("-periodo", "servicio_id")
        )

    def pagos_del_cliente(self, cliente: Cliente) -> QuerySet[Pago]:
        """Devuelve pagos únicos asociados a cualquiera de sus conexiones."""

        return (
            Pago.objects.filter(cuotas__servicio__cliente=cliente)
            .select_related("cuenta")
            .prefetch_related("cuotas__servicio__plan")
            .distinct()
            .order_by("-fecha", "-id")
        )

    def resumir(
        self,
        cliente: Cliente,
        fecha_referencia: date | None = None,
    ) -> ResumenCuentaCorriente:
        """Calcula deuda, vencimientos y cantidades pendientes a una fecha dada."""

        hoy = fecha_referencia or timezone.localdate()
        pendientes = Cuota.objects.filter(servicio__cliente=cliente, pago__isnull=True)
        vencidas = pendientes.filter(fecha_vencimiento__lt=hoy)
        por_vencer = pendientes.filter(fecha_vencimiento__gte=hoy)
        total_pendiente = pendientes.aggregate(total=Sum("monto"))["total"] or Decimal("0.00")
        total_vencido = vencidas.aggregate(total=Sum("monto"))["total"] or Decimal("0.00")
        proxima = (
            por_vencer.order_by("fecha_vencimiento")
            .values_list(
                "fecha_vencimiento",
                flat=True,
            )
            .first()
        )
        return ResumenCuentaCorriente(
            total_pendiente=total_pendiente,
            total_vencido=total_vencido,
            cantidad_pendientes=pendientes.count(),
            cantidad_vencidas=vencidas.count(),
            proximo_vencimiento=proxima,
        )

    def cuotas_pendientes(self, cliente: Cliente) -> QuerySet[Cuota]:
        """Lista las cuotas cancelables de un cliente en orden de vencimiento."""

        return (
            self.cuotas_del_cliente(cliente)
            .filter(pago__isnull=True)
            .order_by(
                "fecha_vencimiento",
                "servicio_id",
            )
        )

    def buscar_clientes_con_cuenta(self, texto: str = "") -> QuerySet[Cliente]:
        """Obtiene clientes y permite buscarlos por nombre o documento."""

        consulta = Cliente.objects.prefetch_related("servicios").all()
        if texto.strip():
            consulta = consulta.filter(
                Q(nombre_razon_social__icontains=texto.strip())
                | Q(numero_documento__icontains=texto.strip())
            )
        return consulta
