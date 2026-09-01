"""Modelos del cliente y sus números de teléfono o WhatsApp."""

from django.db import models

from .validadores import normalizar_documento, normalizar_telefono


class Cliente(models.Model):
    """Representa una persona o empresa que contrata servicios de internet."""

    class TipoDocumento(models.TextChoices):
        """Enumera los documentos admitidos para identificar al cliente."""

        DNI = "DNI", "DNI"
        CUIT = "CUIT", "CUIT"
        PASAPORTE = "PASAPORTE", "Pasaporte"
        OTRO = "OTRO", "Otro"

    class TipoCliente(models.TextChoices):
        """Distingue personas físicas de organizaciones."""

        PERSONA = "PERSONA", "Persona"
        EMPRESA = "EMPRESA", "Empresa"

    class Estado(models.TextChoices):
        """Define los estados utilizados para implementar la baja lógica."""

        ACTIVO = "ACTIVO", "Activo"
        INACTIVO = "INACTIVO", "Inactivo"

    tipo_documento = models.CharField(
        "tipo de documento",
        max_length=20,
        choices=TipoDocumento.choices,
        default=TipoDocumento.DNI,
    )
    numero_documento = models.CharField("número de documento", max_length=30)
    nombre_razon_social = models.CharField("nombre o razón social", max_length=160)
    tipo_cliente = models.CharField(
        "tipo de cliente",
        max_length=30,
        choices=TipoCliente.choices,
        default=TipoCliente.PERSONA,
    )
    contacto_calle = models.CharField("calle de contacto", max_length=120, blank=True)
    contacto_numero = models.CharField("número de contacto", max_length=20, blank=True)
    contacto_localidad = models.CharField("localidad de contacto", max_length=100, blank=True)
    estado = models.CharField(
        "estado",
        max_length=20,
        choices=Estado.choices,
        default=Estado.ACTIVO,
    )

    class Meta:
        """Configura la tabla, el orden y la identidad documental del cliente."""

        db_table = "cliente"
        ordering = ("nombre_razon_social", "id")
        verbose_name = "cliente"
        verbose_name_plural = "clientes"
        constraints = [
            models.UniqueConstraint(
                fields=("tipo_documento", "numero_documento"),
                name="uq_cliente_documento",
            )
        ]

    def save(self, *args, **kwargs):
        """Normaliza el documento y los textos antes de persistirlos."""

        self.numero_documento = normalizar_documento(self.numero_documento)
        self.nombre_razon_social = self.nombre_razon_social.strip()
        self.contacto_calle = self.contacto_calle.strip()
        self.contacto_numero = self.contacto_numero.strip()
        self.contacto_localidad = self.contacto_localidad.strip()
        return super().save(*args, **kwargs)

    def __str__(self):
        """Devuelve el nombre y el documento para identificar al cliente."""

        return f"{self.nombre_razon_social} ({self.tipo_documento} {self.numero_documento})"


class TelefonoCliente(models.Model):
    """Almacena un teléfono único asociado a un solo cliente."""

    numero = models.CharField("teléfono", max_length=30, primary_key=True)
    cliente = models.ForeignKey(
        Cliente,
        on_delete=models.PROTECT,
        related_name="telefonos",
        db_column="id_cliente",
    )

    class Meta:
        """Configura la tabla derivada del atributo multivaluado teléfono."""

        db_table = "cliente_telefono"
        ordering = ("numero",)
        verbose_name = "teléfono del cliente"
        verbose_name_plural = "teléfonos de clientes"

    def save(self, *args, **kwargs):
        """Guarda el teléfono en un formato numérico comparable y uniforme."""

        self.numero = normalizar_telefono(self.numero)
        return super().save(*args, **kwargs)

    def __str__(self):
        """Devuelve el número utilizado como contacto o WhatsApp."""

        return self.numero
