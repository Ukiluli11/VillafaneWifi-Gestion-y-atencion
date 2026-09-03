"""Comprueba que PostgreSQL, el SQL de Workbench y los drawio sigan alineados.

La verificación compara nombres de tablas y columnas. Los tipos del script de
Workbench son equivalentes MySQL para poder importarlos en esa herramienta; la
fuente de verdad de ejecución continúa siendo Django sobre PostgreSQL.
"""

# ruff: noqa: E501

from __future__ import annotations

import os
import re
import sys
from pathlib import Path
from xml.etree import ElementTree as ET

RAIZ = Path(__file__).resolve().parents[1]
SQL = RAIZ / "modelo_logico" / "villafane_wifi_completo_mysql_workbench.sql"
DER = RAIZ / "modelo_logico" / "DER_Logico_Completo_Villafane.drawio"
CLASES = RAIZ / "diagramas" / "Diagrama_de_clases_Villafane_CORREGIDO.drawio"

TABLAS_IMPLEMENTADAS = {
    "usuario", "empleado", "administrador", "cliente", "cliente_telefono",
    "plan", "servicio", "cuenta_receptora", "pago", "cuota",
}
TABLAS_DJANGO = {
    "django_migrations", "django_content_type", "django_session",
    "django_admin_log", "auth_permission", "auth_group",
    "auth_group_permissions",
}
TABLAS_FUTURAS = {
    "conversacion", "mensaje", "comprobante", "ticket", "nota_interna",
}
COLUMNAS_FUTURAS_EN_TABLAS_ACTUALES = {"pago": {"id_comprobante"}}


def leer_columnas_sql() -> dict[str, set[str]]:
    """Obtiene columnas declaradas en cada CREATE TABLE del modelo completo."""

    texto = SQL.read_text(encoding="utf-8")
    patron = re.compile(r"CREATE TABLE\s+(\w+)\s*\((.*?)\) ENGINE=InnoDB", re.S)
    resultado: dict[str, set[str]] = {}
    for coincidencia in patron.finditer(texto):
        nombre, cuerpo = coincidencia.groups()
        columnas: set[str] = set()
        for linea in cuerpo.splitlines():
            limpia = linea.strip()
            if limpia.startswith("CONSTRAINT"):
                break
            columna = re.match(r"^(\w+)\s+[A-Z]", limpia)
            if columna:
                columnas.add(columna.group(1))
        resultado[nombre] = columnas
    return resultado


def cargar_django():
    """Inicializa Django usando la misma configuración local del sistema."""

    sys.path.insert(0, str(RAIZ / "backend"))
    os.environ.setdefault("DJANGO_SETTINGS_MODULE", "config.settings.local")
    import django

    django.setup()


def columnas_postgresql() -> dict[str, set[str]]:
    """Lee mediante Django el esquema que existe efectivamente en PostgreSQL."""

    from django.db import connection

    resultado: dict[str, set[str]] = {}
    with connection.cursor() as cursor:
        for tabla in connection.introspection.table_names(cursor):
            descripcion = connection.introspection.get_table_description(cursor, tabla)
            resultado[tabla] = {columna.name for columna in descripcion}
    return resultado


def verificar_pestanas(ruta: Path, esperadas: set[str]) -> list[str]:
    """Valida el XML drawio y devuelve errores de pestañas faltantes."""

    raiz = ET.parse(ruta).getroot()
    presentes = {diagrama.attrib.get("name", "") for diagrama in raiz.findall("diagram")}
    faltantes = esperadas - presentes
    return [f"{ruta.name}: falta la pestaña {nombre}" for nombre in sorted(faltantes)]


def main() -> int:
    """Ejecuta todas las comprobaciones y finaliza distinto de cero si falla."""

    errores: list[str] = []
    sql = leer_columnas_sql()
    objetivo = TABLAS_IMPLEMENTADAS | TABLAS_DJANGO | TABLAS_FUTURAS
    if set(sql) != objetivo:
        errores.append(
            "Tablas del SQL diferentes al objetivo: "
            f"faltan={sorted(objetivo - set(sql))}, sobran={sorted(set(sql) - objetivo)}"
        )

    cargar_django()
    base = columnas_postgresql()
    actuales = TABLAS_IMPLEMENTADAS | TABLAS_DJANGO
    if actuales - set(base):
        errores.append(f"Faltan tablas actuales en PostgreSQL: {sorted(actuales - set(base))}")
    for tabla in sorted(actuales & set(base) & set(sql)):
        columnas_modelo = sql[tabla] - COLUMNAS_FUTURAS_EN_TABLAS_ACTUALES.get(tabla, set())
        if columnas_modelo != base[tabla]:
            errores.append(
                f"{tabla}: PostgreSQL={sorted(base[tabla])}, Workbench={sorted(columnas_modelo)}"
            )

    errores.extend(verificar_pestanas(DER, {
        "01 - Implementación actual", "02 - Ampliaciones futuras",
        "03 - Infraestructura Django",
    }))
    errores.extend(verificar_pestanas(CLASES, {
        "01 - Dominio actual", "02 - Dominio futuro", "03 - Servicios",
        "04 - Panel Django", "05 - API Django REST Framework",
    }))

    if errores:
        print("ALINEACIÓN INCORRECTA")
        for error in errores:
            print(f"- {error}")
        return 1
    print("ALINEACIÓN CORRECTA")
    print(f"- PostgreSQL actual: {len(actuales)} tablas ({len(TABLAS_IMPLEMENTADAS)} de negocio + {len(TABLAS_DJANGO)} de Django)")
    print(f"- Modelo completo: {len(objetivo)} tablas ({len(TABLAS_FUTURAS)} futuras)")
    print("- DER lógico: 3 pestañas válidas")
    print("- Diagrama de clases: 5 pestañas válidas")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
