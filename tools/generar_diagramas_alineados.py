"""Genera los diagramas lógico y de clases alineados con Django/PostgreSQL."""

# ruff: noqa: E501, UP038

from __future__ import annotations

import ast
import copy
import html
import re
from dataclasses import dataclass, field
from pathlib import Path
from xml.etree import ElementTree as ET

from PIL import Image, ImageDraw, ImageFont

RAIZ = Path(__file__).resolve().parents[1]
SQL = RAIZ / "modelo_logico" / "villafane_wifi_completo_mysql_workbench.sql"
SALIDA_LOGICO = RAIZ / "modelo_logico" / "DER_Logico_Completo_Villafane.drawio"
SALIDA_CLASES = RAIZ / "diagramas" / "Diagrama_de_clases_Villafane_CORREGIDO.drawio"

COLORES = {
    "implementado": ("#E8F5E9", "#1B5E20"),
    "futuro": ("#FFF3E0", "#E65100"),
    "mixto": ("#E3F2FD", "#0D47A1"),
    "django": ("#ECEFF1", "#455A64"),
    "servicio": ("#E8EAF6", "#283593"),
}


@dataclass
class Nodo:
    """Representa una tabla o clase dentro de una página de diagrams.net."""

    clave: str
    titulo: str
    lineas: list[str]
    estado: str = "implementado"
    x: int = 0
    y: int = 0
    ancho: int = 580
    alto: int = 0

    def calcular_alto(self) -> int:
        """Calcula una altura legible según la cantidad de renglones."""

        self.alto = max(120, 58 + len(self.lineas) * 25)
        return self.alto


@dataclass
class Relacion:
    """Define una relación UML o una clave foránea."""

    origen: str
    destino: str
    etiqueta: str
    tipo: str = "asociacion"


@dataclass
class Pagina:
    """Agrupa nodos y relaciones en una pestaña del archivo drawio."""

    identificador: str
    nombre: str
    titulo: str
    nodos: list[Nodo]
    relaciones: list[Relacion] = field(default_factory=list)
    ancho: int = 3600
    alto: int = 2600


def escapar(valor: str) -> str:
    """Escapa texto para usarlo dentro de atributos XML."""

    return html.escape(valor, quote=True)


def distribuir(nodos: list[Nodo], columnas: int, inicio_y: int = 120, espacio_y: int = 760):
    """Ubica una colección de nodos en una grilla regular."""

    for indice, nodo in enumerate(nodos):
        nodo.x = 70 + (indice % columnas) * 690
        nodo.y = inicio_y + (indice // columnas) * espacio_y
        nodo.calcular_alto()


def etiqueta_nodo(nodo: Nodo) -> str:
    """Construye el contenido HTML visible de un nodo."""

    _, borde = COLORES[nodo.estado]
    cuerpo = "".join(
        f'<div style="text-align:left;padding:3px 10px;border-top:1px solid #d7dde2;">{escapar(linea)}</div>'
        for linea in nodo.lineas
    )
    return (
        f'<div style="background:{borde};color:#ffffff;font-size:16px;font-weight:bold;'
        f'padding:9px;text-align:center;">{escapar(nodo.titulo)}</div>{cuerpo}'
    )


def xml_pagina(pagina: Pagina) -> str:
    """Genera una página drawio sin compresión para facilitar su edición."""

    celdas = ['<mxCell id="0"/>', '<mxCell id="1" parent="0"/>']
    celdas.append(
        f'<mxCell id="titulo" value="{escapar(pagina.titulo)}" '
        'style="text;html=1;strokeColor=none;fillColor=none;align=center;verticalAlign=middle;'
        'fontStyle=1;fontSize=25;fontColor=#173F35;" vertex="1" parent="1">'
        f'<mxGeometry x="500" y="20" width="{pagina.ancho - 1000}" height="45" as="geometry"/></mxCell>'
    )
    for nodo in pagina.nodos:
        relleno, borde = COLORES[nodo.estado]
        valor = escapar(etiqueta_nodo(nodo))
        estilo = (
            f"rounded=1;whiteSpace=wrap;html=1;fillColor={relleno};strokeColor={borde};"
            "strokeWidth=2;align=left;verticalAlign=top;fontSize=12;spacing=0;shadow=0;"
        )
        celdas.append(
            f'<mxCell id="n-{escapar(nodo.clave)}" value="{valor}" style="{estilo}" '
            f'vertex="1" parent="1"><mxGeometry x="{nodo.x}" y="{nodo.y}" '
            f'width="{nodo.ancho}" height="{nodo.alto}" as="geometry"/></mxCell>'
        )
    for indice, relacion in enumerate(pagina.relaciones, start=1):
        if relacion.tipo == "herencia":
            flechas = "startArrow=none;endArrow=block;endFill=0;"
        elif relacion.tipo == "dependencia":
            flechas = "startArrow=none;endArrow=open;endFill=0;dashed=1;"
        elif relacion.tipo == "uno_a_uno" or relacion.etiqueta.startswith("1:1"):
            flechas = "startArrow=ERone;startFill=0;endArrow=ERone;endFill=0;"
        elif relacion.tipo == "muchos_a_muchos" or relacion.etiqueta.startswith("N:N"):
            flechas = "startArrow=ERmany;startFill=0;endArrow=ERmany;endFill=0;"
        else:
            flechas = "startArrow=ERmany;startFill=0;endArrow=ERone;endFill=0;"
        celdas.append(
            f'<mxCell id="r-{indice}" value="{escapar(relacion.etiqueta)}" '
            'style="edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;'
            f'{flechas}strokeColor=#52646F;strokeWidth=2;fontSize=11;labelBackgroundColor=#FFFFFF;" '
            f'edge="1" parent="1" source="n-{escapar(relacion.origen)}" '
            f'target="n-{escapar(relacion.destino)}"><mxGeometry relative="1" as="geometry"/></mxCell>'
        )
    leyenda = (
        "Verde: implementado | Naranja: futuro planificado | Azul: ampliación/servicio | "
        "Gris: infraestructura Django | NULL indica participación opcional sin usar cardinalidad 0."
    )
    celdas.append(
        f'<mxCell id="leyenda" value="{escapar(leyenda)}" '
        'style="shape=note;whiteSpace=wrap;html=1;fillColor=#FFFDE7;strokeColor=#B59B00;'
        'fontSize=13;align=left;spacing=10;" vertex="1" parent="1">'
        f'<mxGeometry x="450" y="{pagina.alto - 130}" width="{pagina.ancho - 900}" height="75" as="geometry"/>'
        '</mxCell>'
    )
    return (
        f'<diagram id="{escapar(pagina.identificador)}" name="{escapar(pagina.nombre)}">'
        f'<mxGraphModel dx="{pagina.ancho}" dy="{pagina.alto}" grid="1" gridSize="10" guides="1" '
        f'tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" '
        f'pageWidth="{pagina.ancho}" pageHeight="{pagina.alto}" math="0" shadow="0"><root>'
        f'{"".join(celdas)}</root></mxGraphModel></diagram>'
    )


def guardar_drawio(ruta: Path, paginas: list[Pagina]):
    """Guarda y valida un archivo diagrams.net de varias páginas."""

    contenido = (
        '<mxfile host="app.diagrams.net" agent="Codex" version="29.6.6" type="device">'
        + "".join(xml_pagina(pagina) for pagina in paginas)
        + "</mxfile>"
    )
    ruta.write_text(contenido, encoding="utf-8")
    ET.fromstring(contenido)


def estado_tabla(nombre: str, comentario: str) -> str:
    """Clasifica una tabla según su estado documental."""

    if nombre.startswith(("django_", "auth_")):
        return "django"
    if "AMPLIACIÓN" in comentario:
        return "mixto"
    if "[FUTURO]" in comentario:
        return "futuro"
    return "implementado"


def leer_tablas_sql() -> tuple[dict[str, Nodo], list[Relacion]]:
    """Lee tablas, columnas y claves foráneas del script de Workbench."""

    texto = SQL.read_text(encoding="utf-8")
    patron = re.compile(r"CREATE TABLE\s+(\w+)\s*\((.*?)\) ENGINE=InnoDB(?: COMMENT='([^']*)')?;", re.S)
    nodos: dict[str, Nodo] = {}
    relaciones: list[Relacion] = []
    for coincidencia in patron.finditer(texto):
        nombre, cuerpo, comentario = coincidencia.groups()
        columnas: list[str] = []
        for linea in cuerpo.splitlines():
            limpia = linea.strip().rstrip(",")
            if limpia.startswith("CONSTRAINT"):
                # En el script todas las columnas preceden a las restricciones.
                # Cortar aquí evita interpretar como campos las continuaciones
                # multilínea de una expresión CHECK.
                break
            if not limpia:
                continue
            columna = re.match(r"^(\w+)\s+([A-Z]+(?:\([^)]*\))?(?:\s+UNSIGNED)?)(.*)$", limpia)
            if columna:
                nombre_columna, tipo, resto = columna.groups()
                marca = "NN" if "NOT NULL" in resto else "NULL"
                columnas.append(f"{nombre_columna} : {tipo} [{marca}]")
        primarias = set()
        primaria = re.search(r"PRIMARY KEY\s*\(([^)]+)\)", cuerpo)
        if primaria:
            primarias.update(valor.strip() for valor in primaria.group(1).split(","))
        unicas = set(primarias)
        for unica in re.finditer(r"UNIQUE(?:\s+KEY\s+\w+)?\s*\(([^)]+)\)", cuerpo, re.I):
            columnas_unicas = [valor.strip() for valor in unica.group(1).split(",")]
            if len(columnas_unicas) == 1:
                unicas.add(columnas_unicas[0])
        for indice, linea in enumerate(columnas):
            nombre_columna = linea.split(" :", 1)[0]
            if nombre_columna in primarias:
                columnas[indice] = "PK " + linea
        for foranea in re.finditer(
            r"FOREIGN KEY\s*\((\w+)\)\s*REFERENCES\s+(\w+)\s*\((\w+)\)", cuerpo, re.I | re.S
        ):
            columna, padre, columna_padre = foranea.groups()
            cardinalidad = "1:1" if columna in unicas else "N:1"
            tipo_relacion = "uno_a_uno" if cardinalidad == "1:1" else "asociacion"
            relaciones.append(
                Relacion(nombre, padre, f"{cardinalidad} · {columna} → {columna_padre}", tipo_relacion)
            )
            for indice, linea in enumerate(columnas):
                if linea.removeprefix("PK ").startswith(columna + " :"):
                    columnas[indice] = linea.replace(columna + " :", "FK " + columna + " :", 1)
        nodos[nombre] = Nodo(nombre, nombre.upper(), columnas, estado_tabla(nombre, comentario or ""))
    return nodos, relaciones


def paginas_logicas() -> list[Pagina]:
    """Separa implementación, ampliaciones e infraestructura Django."""

    nodos, relaciones = leer_tablas_sql()
    implementados_nombres = [
        "usuario", "empleado", "administrador", "cliente", "cliente_telefono",
        "plan", "servicio", "cuenta_receptora", "pago", "cuota",
    ]
    futuro_nombres = [
        "usuario", "cliente", "servicio", "pago", "conversacion", "mensaje",
        "comprobante", "ticket", "nota_interna",
    ]
    infraestructura_nombres = [
        "usuario", "django_content_type", "auth_permission", "auth_group",
        "auth_group_permissions", "django_admin_log", "django_session", "django_migrations",
    ]
    implementados = [copy.deepcopy(nodos[nombre]) for nombre in implementados_nombres]
    for nodo in implementados:
        if nodo.clave == "pago":
            nodo.estado = "implementado"
            nodo.lineas = [linea for linea in nodo.lineas if "id_comprobante" not in linea]
    distribuir(implementados, 5, espacio_y=760)
    relaciones_implementadas = [
        r for r in relaciones
        if r.origen in implementados_nombres and r.destino in implementados_nombres
        and not (r.origen == "pago" and r.destino == "comprobante")
    ]
    futuros = [copy.deepcopy(nodos[nombre]) for nombre in futuro_nombres]
    posiciones_futuras = {
        "cliente": (70, 130), "conversacion": (760, 130),
        "usuario": (1450, 130), "servicio": (2350, 130),
        "mensaje": (760, 760), "ticket": (2350, 760),
        "comprobante": (760, 1390), "pago": (1450, 1390),
        "nota_interna": (2350, 1390),
    }
    for nodo in futuros:
        nodo.x, nodo.y = posiciones_futuras[nodo.clave]
        nodo.calcular_alto()
    relaciones_futuras = [r for r in relaciones if r.origen in futuro_nombres and r.destino in futuro_nombres]
    infraestructura = [nodos[nombre] for nombre in infraestructura_nombres]
    distribuir(infraestructura, 4, espacio_y=760)
    relaciones_infra = [
        r for r in relaciones if r.origen in infraestructura_nombres and r.destino in infraestructura_nombres
    ]
    return [
        Pagina("logico-actual", "01 - Implementación actual", "DER lógico - tablas actualmente implementadas", implementados, relaciones_implementadas, 3550, 1800),
        Pagina("logico-futuro", "02 - Ampliaciones futuras", "DER lógico - entidades futuras y tablas relacionadas", futuros, relaciones_futuras, 3550, 1850),
        Pagina("logico-django", "03 - Infraestructura Django", "DER lógico - tablas generadas por Django", infraestructura, relaciones_infra, 2900, 1800),
    ]


def nodos_dominio() -> tuple[list[Nodo], list[Relacion]]:
    """Define las clases persistentes actuales y las entidades futuras acordadas."""

    datos = [
        ("Usuario", "implementado", ["hereda: AbstractBaseUser", "id, password, last_login", "nombre_usuario", "is_active, is_staff, is_superuser", "fecha_alta", "+ has_perm()", "+ has_module_perms()"]),
        ("Empleado", "implementado", ["hereda: models.Model", "id_usuario (PK/FK)", "area"]),
        ("Administrador", "implementado", ["hereda: models.Model", "id_usuario (PK/FK)"]),
        ("Cliente", "implementado", ["hereda: models.Model", "id", "tipo_documento, numero_documento", "nombre_razon_social, tipo_cliente", "contacto_calle, contacto_numero", "contacto_localidad, estado", "+ save()"]),
        ("TelefonoCliente", "implementado", ["hereda: models.Model", "numero (PK)", "id_cliente (FK)", "+ save()"]),
        ("Plan", "implementado", ["hereda: models.Model", "id, nombre", "velocidad_mbps", "precio_vigente, estado", "+ save()"]),
        ("Servicio", "implementado", ["hereda: models.Model", "id, id_cliente, id_plan", "instalacion_calle, instalacion_numero", "instalacion_localidad, dia_vencimiento", "fecha_alta, ip, mac, estado", "+ save()"]),
        ("CuentaReceptora", "implementado", ["hereda: models.Model", "id, nombre, tipo", "identificador, estado", "+ save()"]),
        ("Pago", "mixto", ["hereda: models.Model", "id, fecha, monto_total, medio_pago", "id_cuenta", "id_comprobante [futuro]"]),
        ("Cuota", "implementado", ["hereda: models.Model", "id, periodo, monto", "fecha_emision, fecha_vencimiento", "id_servicio, id_pago", "+ estado_calculado", "+ estado_mostrado"]),
        ("Conversacion <<futuro>>", "futuro", ["heredará: models.Model", "id, numero_whatsapp", "fechas de inicio/cierre y atención", "estado, modo_atencion", "id_cliente, id_usuario_atencion"]),
        ("Mensaje <<futuro>>", "futuro", ["heredará: models.Model", "id, id_mensaje_externo", "fecha_hora, tipo, contenido", "archivo_adjunto, tipo_emisor", "estado_envio, id_conversacion", "id_usuario_emisor"]),
        ("Comprobante <<futuro>>", "futuro", ["heredará: models.Model", "id, hash_archivo", "fecha_recepcion, numero_operacion", "monto_ocr, fecha_ocr, confianza_ocr", "estado_validacion, motivo_rechazo", "id_mensaje, id_usuario_validador"]),
        ("Ticket <<futuro>>", "futuro", ["heredará: models.Model", "id, fecha_creacion, tipo", "descripcion, estado", "fecha_resolucion, fecha_asignacion", "id_conversacion, id_servicio", "id_usuario_responsable"]),
        ("NotaInterna <<futuro>>", "futuro", ["heredará: models.Model", "id, fecha_hora, contenido", "id_ticket, id_usuario"]),
    ]
    nodos = [Nodo(nombre.split()[0], nombre, lineas, estado) for nombre, estado, lineas in datos]
    distribuir(nodos, 5, espacio_y=780)
    relaciones = [
        Relacion("Empleado", "Usuario", "ES", "herencia"),
        Relacion("Administrador", "Usuario", "ES", "herencia"),
        Relacion("TelefonoCliente", "Cliente", "N:1"), Relacion("Servicio", "Cliente", "N:1"),
        Relacion("Servicio", "Plan", "N:1"), Relacion("Cuota", "Servicio", "N:1"),
        Relacion("Pago", "CuentaReceptora", "N:1"), Relacion("Cuota", "Pago", "N:1"),
        Relacion("Conversacion", "Cliente", "N:1"), Relacion("Conversacion", "Usuario", "N:1"),
        Relacion("Mensaje", "Conversacion", "N:1"), Relacion("Mensaje", "Usuario", "N:1"),
        Relacion("Comprobante", "Mensaje", "1:1"), Relacion("Comprobante", "Usuario", "N:1"),
        Relacion("Pago", "Comprobante", "1:1"), Relacion("Ticket", "Conversacion", "N:1"),
        Relacion("Ticket", "Servicio", "N:1"), Relacion("Ticket", "Usuario", "N:1"),
        Relacion("NotaInterna", "Ticket", "N:1"), Relacion("NotaInterna", "Usuario", "N:1"),
    ]
    return nodos, relaciones


def nodos_servicios() -> tuple[list[Nodo], list[Relacion]]:
    """Representa las clases que concentran casos de uso y reglas de negocio."""

    datos = [
        ("GestorUsuario", "implementado", ["hereda: BaseUserManager", "+ crear_usuario()", "+ crear_superusuario()", "+ create_user()", "+ create_superuser()"]),
        ("ServicioUsuarios", "implementado", ["+ crear_empleado()", "+ crear_administrador()"]),
        ("ServicioAutorizacion", "implementado", ["+ politica_para()", "+ acciones_permitidas()", "+ puede()"]),
        ("PoliticaAcceso", "servicio", ["<<abstracta>>", "+ acciones_permitidas()"]),
        ("PoliticaAdministrador", "implementado", ["+ acciones_permitidas()"]),
        ("PoliticaEmpleado", "implementado", ["+ acciones_permitidas()"]),
        ("ServicioClientes", "implementado", ["+ buscar()", "+ actualizar()", "+ dar_de_baja()"]),
        ("CasoUsoAltaIntegralCliente", "implementado", ["+ ejecutar()"]),
        ("ServicioPlanes", "implementado", ["+ crear()", "+ actualizar()", "+ dar_de_baja()"]),
        ("ServicioContrataciones", "implementado", ["+ crear()", "+ actualizar()", "+ dar_de_baja()"]),
        ("ServicioFacturacion", "implementado", ["+ generar_para_servicios_activos()", "+ registrar_pago()"]),
        ("ServicioCuentasReceptoras", "implementado", ["+ crear()"]),
        ("ServicioCuentaCorriente", "implementado", ["+ obtener_resumen()", "+ listar_clientes()"]),
        ("ResumenCuentaCorriente", "implementado", ["<<dataclass>>", "deuda_total, deuda_vencida", "proximo_vencimiento"]),
        ("AdaptadorWhatsApp", "futuro", ["+ recibir_webhook()", "+ enviar_mensaje()"]),
        ("ServicioInterpretacionIA", "futuro", ["+ interpretar_intencion()", "+ generar_respuesta()"]),
        ("ServicioOCR", "futuro", ["+ extraer_datos()", "+ calcular_confianza()"]),
        ("ServicioTickets", "futuro", ["+ crear_en_cola()", "+ tomar_siguiente()", "+ resolver()"]),
    ]
    nodos = [Nodo(nombre, nombre, lineas, estado) for nombre, estado, lineas in datos]
    distribuir(nodos, 5, espacio_y=630)
    relaciones = [
        Relacion("PoliticaAdministrador", "PoliticaAcceso", "hereda", "herencia"),
        Relacion("PoliticaEmpleado", "PoliticaAcceso", "hereda", "herencia"),
        Relacion("ServicioAutorizacion", "PoliticaAcceso", "selecciona", "dependencia"),
        Relacion("CasoUsoAltaIntegralCliente", "ServicioClientes", "coordina", "dependencia"),
        Relacion("CasoUsoAltaIntegralCliente", "ServicioContrataciones", "coordina", "dependencia"),
        Relacion("ServicioCuentaCorriente", "ResumenCuentaCorriente", "construye", "dependencia"),
        Relacion("AdaptadorWhatsApp", "ServicioInterpretacionIA", "utiliza", "dependencia"),
        Relacion("AdaptadorWhatsApp", "ServicioOCR", "utiliza", "dependencia"),
    ]
    return nodos, relaciones


def nombre_base(base: ast.expr) -> str:
    """Devuelve el nombre corto de una clase base encontrada en el AST."""

    return ast.unparse(base).split(".")[-1]


def nodos_interfaces() -> list[Nodo]:
    """Extrae automáticamente formularios, vistas y serializadores implementados."""

    patrones = ["forms.py", "views.py", "vistas_panel.py", "serializadores.py", "mixins.py", "permisos_drf.py"]
    nodos: list[Nodo] = []
    for ruta in sorted((RAIZ / "backend" / "apps").glob("*/*.py")):
        if ruta.name not in patrones:
            continue
        arbol = ast.parse(ruta.read_text(encoding="utf-8"))
        modulo = ruta.parent.name + "." + ruta.stem
        for clase in (n for n in arbol.body if isinstance(n, ast.ClassDef)):
            bases = ", ".join(nombre_base(base) for base in clase.bases) or "object"
            metodos = [n.name + "()" for n in clase.body if isinstance(n, (ast.FunctionDef, ast.AsyncFunctionDef))]
            lineas = [f"módulo: {modulo}", f"hereda: {bases}"] + ["+ " + nombre for nombre in metodos[:5]]
            nodos.append(Nodo(f"{modulo}-{clase.name}", clase.name, lineas, "django" if bases != "object" else "implementado", ancho=530))
    distribuir(nodos, 6, espacio_y=500)
    return nodos


def paginas_clases() -> list[Pagina]:
    """Crea páginas separadas para dominio, servicios e interfaces Django/DRF."""

    dominio, relaciones_dominio = nodos_dominio()
    actuales = [copy.deepcopy(nodo) for nodo in dominio if nodo.estado != "futuro"]
    for nodo in actuales:
        if nodo.clave == "Pago":
            nodo.estado = "implementado"
            nodo.lineas = [linea for linea in nodo.lineas if "id_comprobante" not in linea]
    actuales_claves = {nodo.clave for nodo in actuales}
    distribuir(actuales, 5, espacio_y=760)
    relaciones_actuales = [
        relacion for relacion in relaciones_dominio
        if relacion.origen in actuales_claves and relacion.destino in actuales_claves
        and not (relacion.origen == "Pago" and relacion.destino == "Comprobante")
    ]
    futuras_claves = {"Usuario", "Cliente", "Servicio", "Pago", "Conversacion", "Mensaje", "Comprobante", "Ticket", "NotaInterna"}
    futuras = [copy.deepcopy(nodo) for nodo in dominio if nodo.clave in futuras_claves]
    posiciones_futuras = {
        "Cliente": (70, 130), "Conversacion": (760, 130),
        "Usuario": (1450, 130), "Servicio": (2350, 130),
        "Mensaje": (760, 760), "Ticket": (2350, 760),
        "Comprobante": (760, 1390), "Pago": (1450, 1390),
        "NotaInterna": (2350, 1390),
    }
    for nodo in futuras:
        nodo.x, nodo.y = posiciones_futuras[nodo.clave]
        nodo.calcular_alto()
    relaciones_futuras = [
        relacion for relacion in relaciones_dominio
        if relacion.origen in futuras_claves and relacion.destino in futuras_claves
    ]
    servicios, relaciones_servicios = nodos_servicios()
    interfaces = nodos_interfaces()
    modulos_panel = {
        "comun.mixins", "comun.views", "usuarios.forms", "usuarios.mixins",
        "usuarios.views", "usuarios.vistas_panel", "clientes.forms",
        "clientes.vistas_panel", "servicios.forms", "servicios.vistas_panel",
        "facturacion.forms", "facturacion.vistas_panel",
    }
    interfaces_panel = [
        copy.deepcopy(nodo) for nodo in interfaces
        if nodo.lineas[0].removeprefix("módulo: ") in modulos_panel
    ]
    interfaces_api = [
        copy.deepcopy(nodo) for nodo in interfaces
        if nodo.lineas[0].removeprefix("módulo: ") not in modulos_panel
    ]
    distribuir(interfaces_panel, 5, espacio_y=530)
    distribuir(interfaces_api, 5, espacio_y=530)
    filas_panel = (len(interfaces_panel) + 4) // 5
    filas_api = (len(interfaces_api) + 4) // 5
    return [
        Pagina("clases-actual", "01 - Dominio actual", "Diagrama de clases - dominio actualmente implementado", actuales, relaciones_actuales, 3550, 1800),
        Pagina("clases-futuro", "02 - Dominio futuro", "Diagrama de clases - ampliaciones futuras y clases relacionadas", futuras, relaciones_futuras, 3550, 1850),
        Pagina("clases-servicios", "03 - Servicios", "Diagrama de clases - servicios y reglas de negocio", servicios, relaciones_servicios, 3550, 2450),
        Pagina("clases-panel", "04 - Panel Django", "Diagrama de clases - formularios, vistas y mixins del panel", interfaces_panel, [], 3550, max(1800, 180 + filas_panel * 530)),
        Pagina("clases-api", "05 - API Django REST Framework", "Diagrama de clases - vistas, serializadores y permisos de la API", interfaces_api, [], 3550, max(1800, 180 + filas_api * 530)),
    ]


def fuente(tamano: int, negrita: bool = False):
    """Carga una fuente legible para las previsualizaciones PNG."""

    nombre = "arialbd.ttf" if negrita else "arial.ttf"
    try:
        return ImageFont.truetype(nombre, tamano)
    except OSError:
        return ImageFont.load_default()


def previsualizar(pagina: Pagina, ruta: Path):
    """Genera una imagen de control basada en la misma geometría del drawio."""

    escala = 0.45
    imagen = Image.new("RGB", (int(pagina.ancho * escala), int(pagina.alto * escala)), "white")
    dibujo = ImageDraw.Draw(imagen)
    dibujo.text((pagina.ancho * escala / 2, 12), pagina.titulo, anchor="ma", font=fuente(18, True), fill="#173F35")
    mapa = {n.clave: n for n in pagina.nodos}
    for relacion in pagina.relaciones:
        origen, destino = mapa.get(relacion.origen), mapa.get(relacion.destino)
        if origen and destino:
            puntos = [
                ((origen.x + origen.ancho / 2) * escala, (origen.y + origen.alto / 2) * escala),
                ((destino.x + destino.ancho / 2) * escala, (destino.y + destino.alto / 2) * escala),
            ]
            dibujo.line(puntos, fill="#78909C", width=2)
    for nodo in pagina.nodos:
        relleno, borde = COLORES[nodo.estado]
        caja = tuple(int(v * escala) for v in (nodo.x, nodo.y, nodo.x + nodo.ancho, nodo.y + nodo.alto))
        dibujo.rounded_rectangle(caja, radius=5, fill=relleno, outline=borde, width=2)
        cabecera = (caja[0], caja[1], caja[2], caja[1] + 25)
        dibujo.rectangle(cabecera, fill=borde)
        dibujo.text(((caja[0] + caja[2]) / 2, caja[1] + 12), nodo.titulo, anchor="mm", font=fuente(10, True), fill="white")
        y = caja[1] + 31
        for linea in nodo.lineas:
            dibujo.text((caja[0] + 6, y), linea[:65], font=fuente(8), fill="#263238")
            y += 12
    imagen.save(ruta)


def principal():
    """Genera, valida y previsualiza ambos modelos."""

    paginas_logico = paginas_logicas()
    paginas_clase = paginas_clases()
    guardar_drawio(SALIDA_LOGICO, paginas_logico)
    guardar_drawio(SALIDA_CLASES, paginas_clase)
    for pagina in paginas_logico:
        previsualizar(pagina, SALIDA_LOGICO.with_name(f"{SALIDA_LOGICO.stem}_{pagina.identificador}.png"))
    for pagina in paginas_clase:
        previsualizar(pagina, SALIDA_CLASES.with_name(f"{SALIDA_CLASES.stem}_{pagina.identificador}.png"))
    print(SALIDA_LOGICO)
    print(SALIDA_CLASES)
    print(f"tablas={sum(len(p.nodos) for p in paginas_logico)} (usuario se repite en infraestructura)")
    print(f"paginas_clases={len(paginas_clase)}")


if __name__ == "__main__":
    principal()
