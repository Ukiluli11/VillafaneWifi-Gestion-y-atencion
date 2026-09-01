"""Servicios de aplicación para las reglas de gestión de clientes."""

from django.core.exceptions import ValidationError
from django.db import transaction
from django.db.models import Q, QuerySet

from .models import Cliente, TelefonoCliente
from .validadores import normalizar_documento, normalizar_telefono, validar_telefono


class ServicioClientes:
    """Centraliza el alta, la edición, la baja lógica y la búsqueda de clientes."""

    CAMPOS_EDITABLES = {
        "tipo_documento",
        "numero_documento",
        "nombre_razon_social",
        "tipo_cliente",
        "contacto_calle",
        "contacto_numero",
        "contacto_localidad",
    }

    @transaction.atomic
    def crear(self, datos: dict, telefonos: list[str]) -> Cliente:
        """Registra un cliente activo con uno o más teléfonos únicos."""

        if not telefonos:
            raise ValidationError("Debe indicar al menos un teléfono o WhatsApp.")
        datos_limpios = dict(datos)
        datos_limpios["numero_documento"] = normalizar_documento(
            datos_limpios.get("numero_documento", "")
        )
        cliente = Cliente(**datos_limpios)
        cliente.full_clean()
        cliente.save()
        self._reemplazar_telefonos(cliente, telefonos)
        return cliente

    @transaction.atomic
    def actualizar(
        self,
        cliente: Cliente,
        datos: dict,
        telefonos: list[str] | None = None,
    ) -> Cliente:
        """Modifica los datos permitidos y opcionalmente reemplaza sus teléfonos."""

        for campo, valor in datos.items():
            if campo in self.CAMPOS_EDITABLES:
                setattr(cliente, campo, valor)
        cliente.numero_documento = normalizar_documento(cliente.numero_documento)
        cliente.full_clean()
        cliente.save()
        if telefonos is not None:
            if not telefonos:
                raise ValidationError("El cliente debe conservar al menos un teléfono.")
            self._reemplazar_telefonos(cliente, telefonos)
        return cliente

    @transaction.atomic
    def dar_de_baja(self, cliente: Cliente) -> Cliente:
        """Inactiva al cliente y sus conexiones sin eliminar información histórica."""

        from apps.servicios.servicios import ServicioContrataciones

        cliente.estado = Cliente.Estado.INACTIVO
        cliente.save(update_fields=["estado"])
        ServicioContrataciones().dar_de_baja_por_cliente(cliente)
        return cliente

    def buscar(self, termino: str) -> QuerySet[Cliente]:
        """Busca por documento, nombre, teléfono/WhatsApp o localidad."""

        termino = termino.strip()
        if not termino:
            return self.listar()

        consulta = Q(nombre_razon_social__icontains=termino) | Q(
            contacto_localidad__icontains=termino
        )
        documento = normalizar_documento(termino)
        if documento:
            consulta |= Q(numero_documento__icontains=documento)
        telefono = normalizar_telefono(termino)
        if telefono:
            consulta |= Q(telefonos__numero__icontains=telefono)
        return self.listar().filter(consulta).distinct()

    def listar(self) -> QuerySet[Cliente]:
        """Devuelve clientes con teléfonos, servicios y planes precargados."""

        return Cliente.objects.prefetch_related("telefonos", "servicios__plan").all()

    def _reemplazar_telefonos(self, cliente: Cliente, telefonos: list[str]) -> None:
        """Sustituye los contactos dentro de la transacción activa."""

        telefonos_normalizados = list(dict.fromkeys(validar_telefono(item) for item in telefonos))
        cliente.telefonos.all().delete()
        for numero in telefonos_normalizados:
            telefono = TelefonoCliente(cliente=cliente, numero=numero)
            telefono.full_clean()
            telefono.save()
