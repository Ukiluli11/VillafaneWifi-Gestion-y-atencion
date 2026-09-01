"""Normalizadores y validadores de identificadores de clientes."""

import re

from django.core.exceptions import ValidationError


def normalizar_documento(numero: str) -> str:
    """Quita separadores y unifica en mayúsculas el número de documento."""

    return re.sub(r"[\s.\-]", "", str(numero)).upper()


def normalizar_telefono(numero: str) -> str:
    """Conserva únicamente los dígitos para impedir duplicados por formato."""

    return re.sub(r"\D", "", str(numero))


def validar_telefono(numero: str) -> str:
    """Valida que un teléfono normalizado posea entre 8 y 15 dígitos."""

    telefono = normalizar_telefono(numero)
    if not 8 <= len(telefono) <= 15:
        raise ValidationError("El teléfono debe contener entre 8 y 15 dígitos.")
    return telefono
