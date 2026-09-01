#!/usr/bin/env python
"""Punto de entrada para ejecutar los comandos administrativos de Django."""
import os
import sys


def principal():
    """Configura Django y delega la ejecución al comando solicitado."""
    os.environ.setdefault("DJANGO_SETTINGS_MODULE", "config.settings.local")
    try:
        from django.core.management import execute_from_command_line
    except ImportError as exc:
        raise ImportError(
            "Django no está instalado. Ejecutá: pip install -r requirements/dev.txt"
        ) from exc
    execute_from_command_line(sys.argv)


if __name__ == "__main__":
    principal()

