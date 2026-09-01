"""Modelos del catálogo de planes y los servicios contratados."""

from django.core.validators import MaxValueValidator, MinValueValidator, RegexValidator
from django.db import models
from django.db.models.functions import Lower
from django.utils import timezone

from apps.clientes.models import Cliente


class Plan(models.Model):
    """Representa una opción comercial vigente o histórica del proveedor."""

    class Estado(models.TextChoices):
        """Define si un plan puede asignarse a nuevas contrataciones."""

        ACTIVO = "ACTIVO", "Activo"
        INACTIVO = "INACTIVO", "Inactivo"

    nombre = models.CharField("nombre", max_length=100)
    velocidad_mbps = models.PositiveIntegerField(
        "velocidad en Mbps",
        validators=[MinValueValidator(1)],
    )
    precio_vigente = models.DecimalField(
        "precio vigente",
        max_digits=12,
        decimal_places=2,
        validators=[MinValueValidator(0)],
    )
    estado = models.CharField(
        "estado",
        max_length=20,
        choices=Estado.choices,
        default=Estado.ACTIVO,
    )

    class Meta:
        """Configura la tabla y evita nombres de plan duplicados por mayúsculas."""

        db_table = "plan"
        ordering = ("velocidad_mbps", "nombre")
        verbose_name = "plan"
        verbose_name_plural = "planes"
        constraints = [
            models.UniqueConstraint(Lower("nombre"), name="uq_plan_nombre_minusculas"),
            models.CheckConstraint(
                condition=models.Q(velocidad_mbps__gt=0),
                name="ck_plan_velocidad_positiva",
            ),
            models.CheckConstraint(
                condition=models.Q(precio_vigente__gte=0),
                name="ck_plan_precio_no_negativo",
            ),
        ]

    def save(self, *args, **kwargs):
        """Elimina espacios accidentales del nombre antes de persistirlo."""

        self.nombre = self.nombre.strip()
        return super().save(*args, **kwargs)

    def __str__(self):
        """Devuelve una descripción comercial breve del plan."""

        return f"{self.nombre} - {self.velocidad_mbps} Mbps"


class Servicio(models.Model):
    """Representa una conexión de internet contratada por un cliente."""

    class Estado(models.TextChoices):
        """Enumera las situaciones operativas de una conexión."""

        ACTIVO = "ACTIVO", "Activo"
        SUSPENDIDO = "SUSPENDIDO", "Suspendido"
        INACTIVO = "INACTIVO", "Inactivo"

    cliente = models.ForeignKey(
        Cliente,
        on_delete=models.PROTECT,
        related_name="servicios",
        db_column="id_cliente",
    )
    plan = models.ForeignKey(
        Plan,
        on_delete=models.PROTECT,
        related_name="servicios",
        db_column="id_plan",
    )
    instalacion_calle = models.CharField("calle de instalación", max_length=120)
    instalacion_numero = models.CharField("número de instalación", max_length=20, blank=True)
    instalacion_localidad = models.CharField("localidad de instalación", max_length=100)
    dia_vencimiento = models.PositiveSmallIntegerField(
        "día de vencimiento",
        validators=[MinValueValidator(1), MaxValueValidator(31)],
    )
    fecha_alta = models.DateField("fecha de alta", default=timezone.localdate)
    ip = models.GenericIPAddressField("dirección IP", protocol="both", blank=True, null=True)
    mac = models.CharField(
        "dirección MAC",
        max_length=17,
        blank=True,
        null=True,
        validators=[
            RegexValidator(
                regex=r"^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$",
                message="La dirección MAC debe tener seis pares hexadecimales.",
            )
        ],
    )
    estado = models.CharField(
        "estado",
        max_length=20,
        choices=Estado.choices,
        default=Estado.ACTIVO,
    )

    class Meta:
        """Configura la tabla y las restricciones físicas de la conexión."""

        db_table = "servicio"
        ordering = ("cliente_id", "id")
        verbose_name = "servicio"
        verbose_name_plural = "servicios"
        constraints = [
            models.UniqueConstraint(fields=("ip",), name="uq_servicio_ip"),
            models.UniqueConstraint(fields=("mac",), name="uq_servicio_mac"),
            models.CheckConstraint(
                condition=models.Q(dia_vencimiento__gte=1, dia_vencimiento__lte=31),
                name="ck_servicio_dia_vencimiento",
            ),
        ]

    def save(self, *args, **kwargs):
        """Normaliza la dirección y la MAC antes de persistir la conexión."""

        self.instalacion_calle = self.instalacion_calle.strip()
        self.instalacion_numero = self.instalacion_numero.strip()
        self.instalacion_localidad = self.instalacion_localidad.strip()
        self.ip = self.ip or None
        if self.mac:
            self.mac = self.mac.strip().replace("-", ":").upper()
        return super().save(*args, **kwargs)

    def __str__(self):
        """Devuelve una referencia breve de la conexión y su cliente."""

        return f"Servicio {self.pk} de {self.cliente.nombre_razon_social}"
