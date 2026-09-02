"""Modelos de cuotas, pagos y cuentas receptoras de Villafañe Wifi."""

from decimal import Decimal

from django.core.validators import MinValueValidator, RegexValidator
from django.db import models
from django.utils import timezone

from apps.servicios.models import Servicio


class CuentaReceptora(models.Model):
    """Representa una cuenta de la empresa habilitada para recibir pagos."""

    class Tipo(models.TextChoices):
        """Enumera los tipos de cuenta admitidos por la organización."""

        BANCO = "BANCO", "Cuenta bancaria"
        BILLETERA = "BILLETERA", "Billetera virtual"
        CAJA = "CAJA", "Caja / efectivo"
        OTRA = "OTRA", "Otra"

    class Estado(models.TextChoices):
        """Indica si la cuenta puede seleccionarse en nuevos pagos."""

        ACTIVA = "ACTIVA", "Activa"
        INACTIVA = "INACTIVA", "Inactiva"

    nombre = models.CharField("nombre", max_length=100)
    tipo = models.CharField("tipo", max_length=30, choices=Tipo.choices)
    identificador = models.CharField(
        "identificador",
        max_length=100,
        help_text="Alias, CBU/CVU, nombre de caja u otro dato identificatorio.",
    )
    estado = models.CharField(
        "estado",
        max_length=20,
        choices=Estado.choices,
        default=Estado.ACTIVA,
    )

    class Meta:
        """Configura la tabla y evita repetir una misma cuenta por tipo."""

        db_table = "cuenta_receptora"
        ordering = ("nombre", "id")
        verbose_name = "cuenta receptora"
        verbose_name_plural = "cuentas receptoras"
        constraints = [
            models.UniqueConstraint(
                fields=("tipo", "identificador"),
                name="uq_cuenta_identificador",
            )
        ]

    def save(self, *args, **kwargs):
        """Normaliza los textos utilizados para identificar la cuenta."""

        self.nombre = self.nombre.strip()
        self.identificador = self.identificador.strip()
        return super().save(*args, **kwargs)

    def __str__(self):
        """Devuelve una descripción apta para formularios y listados."""

        return f"{self.nombre} · {self.identificador}"


class Pago(models.Model):
    """Registra una cobranza que puede cancelar una o varias cuotas completas."""

    class Medio(models.TextChoices):
        """Enumera los medios de pago utilizados actualmente por la empresa."""

        TRANSFERENCIA = "TRANSFERENCIA", "Transferencia"
        EFECTIVO = "EFECTIVO", "Efectivo"
        MERCADO_PAGO = "MERCADO_PAGO", "Mercado Pago"
        OTRO = "OTRO", "Otro"

    cuenta = models.ForeignKey(
        CuentaReceptora,
        on_delete=models.PROTECT,
        related_name="pagos",
        db_column="id_cuenta",
    )
    fecha = models.DateTimeField("fecha y hora", default=timezone.now)
    monto_total = models.DecimalField(
        "monto total",
        max_digits=12,
        decimal_places=2,
        validators=[MinValueValidator(Decimal("0.01"))],
    )
    medio_pago = models.CharField("medio de pago", max_length=30, choices=Medio.choices)

    class Meta:
        """Configura la tabla y su orden cronológico predeterminado."""

        db_table = "pago"
        ordering = ("-fecha", "-id")
        verbose_name = "pago"
        verbose_name_plural = "pagos"
        constraints = [
            models.CheckConstraint(
                condition=models.Q(monto_total__gt=0),
                name="ck_pago_monto_positivo",
            )
        ]
        indexes = [models.Index(fields=("cuenta", "fecha"), name="ix_pago_cuenta_fecha")]

    def __str__(self):
        """Devuelve una referencia breve de la cobranza."""

        return f"Pago #{self.pk} · ${self.monto_total}"


class Cuota(models.Model):
    """Conserva el importe mensual facturado a una conexión en un período."""

    servicio = models.ForeignKey(
        Servicio,
        on_delete=models.PROTECT,
        related_name="cuotas",
        db_column="id_servicio",
    )
    pago = models.ForeignKey(
        Pago,
        on_delete=models.PROTECT,
        related_name="cuotas",
        db_column="id_pago",
        blank=True,
        null=True,
    )
    periodo = models.CharField(
        "período",
        max_length=7,
        validators=[
            RegexValidator(
                regex=r"^\d{4}-(0[1-9]|1[0-2])$",
                message="El período debe utilizar el formato AAAA-MM.",
            )
        ],
    )
    monto = models.DecimalField(
        "monto",
        max_digits=12,
        decimal_places=2,
        validators=[MinValueValidator(Decimal("0.01"))],
    )
    fecha_emision = models.DateField("fecha de emisión")
    fecha_vencimiento = models.DateField("fecha de vencimiento")

    class Meta:
        """Configura la identidad mensual y las restricciones de la cuota."""

        db_table = "cuota"
        ordering = ("-periodo", "servicio_id")
        verbose_name = "cuota"
        verbose_name_plural = "cuotas"
        constraints = [
            models.UniqueConstraint(
                fields=("servicio", "periodo"),
                name="uq_cuota_servicio_periodo",
            ),
            models.CheckConstraint(
                condition=models.Q(monto__gt=0),
                name="ck_cuota_monto_positivo",
            ),
            models.CheckConstraint(
                condition=models.Q(fecha_vencimiento__gte=models.F("fecha_emision")),
                name="ck_cuota_fechas",
            ),
        ]
        indexes = [
            models.Index(fields=("pago",), name="ix_cuota_pago"),
            models.Index(fields=("fecha_vencimiento",), name="ix_cuota_vencimiento"),
        ]

    @property
    def estado_calculado(self) -> str:
        """Calcula el estado sin almacenar un dato derivable y redundante."""

        if self.pago_id:
            return "PAGADA"
        if self.fecha_vencimiento < timezone.localdate():
            return "VENCIDA"
        return "PENDIENTE"

    @property
    def estado_mostrado(self) -> str:
        """Devuelve el estado calculado con un formato legible."""

        return self.estado_calculado.capitalize()

    def __str__(self):
        """Identifica la cuota con los datos útiles para seleccionarla en un pago."""

        vencimiento = self.fecha_vencimiento.strftime("%d/%m/%Y")
        return (
            f"{self.periodo} · Servicio #{self.servicio_id} · "
            f"${self.monto} · vence {vencimiento}"
        )
