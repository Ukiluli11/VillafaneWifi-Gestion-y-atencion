"""Políticas orientadas a objetos para autorizar funciones del sistema."""

from abc import ABC, abstractmethod
from enum import StrEnum

from .models import Administrador, Empleado, Usuario


class AccionSistema(StrEnum):
    """Enumera las operaciones funcionales protegidas por RF-30."""

    CONSULTAR_CLIENTES = "consultar_clientes"
    GESTIONAR_CLIENTES = "gestionar_clientes"
    CONSULTAR_PLANES = "consultar_planes"
    GESTIONAR_PLANES = "gestionar_planes"
    CONSULTAR_SERVICIOS = "consultar_servicios"
    GESTIONAR_SERVICIOS = "gestionar_servicios"
    CONSULTAR_CUENTAS = "consultar_cuentas"
    GESTIONAR_CUENTAS = "gestionar_cuentas"
    CONSULTAR_PAGOS = "consultar_pagos"
    GESTIONAR_PAGOS = "gestionar_pagos"
    CONSULTAR_REPORTES = "consultar_reportes"
    ATENDER_CONVERSACIONES = "atender_conversaciones"
    CONSULTAR_TICKETS = "consultar_tickets"
    REGISTRAR_TICKETS = "registrar_tickets"
    GESTIONAR_TICKETS = "gestionar_tickets"
    GESTIONAR_USUARIOS = "gestionar_usuarios"


class PoliticaAcceso(ABC):
    """Define el contrato común de una política de autorización."""

    @abstractmethod
    def acciones_permitidas(self) -> frozenset[AccionSistema]:
        """Devuelve las acciones habilitadas por la política concreta."""

    def permite(self, accion: AccionSistema) -> bool:
        """Indica si una acción forma parte del conjunto autorizado."""

        return accion in self.acciones_permitidas()


class PoliticaAdministrador(PoliticaAcceso):
    """Concede al subtipo administrador todas las acciones definidas."""

    def acciones_permitidas(self) -> frozenset[AccionSistema]:
        """Devuelve el catálogo completo de acciones del sistema."""

        return frozenset(AccionSistema)


class PoliticaEmpleado(PoliticaAcceso):
    """Resuelve los permisos de un empleado según su área de trabajo."""

    ACCIONES_POR_AREA = {
        Empleado.Area.ADMINISTRACION: frozenset(
            {
                AccionSistema.CONSULTAR_CLIENTES,
                AccionSistema.GESTIONAR_CLIENTES,
                AccionSistema.CONSULTAR_PLANES,
                AccionSistema.GESTIONAR_PLANES,
                AccionSistema.CONSULTAR_SERVICIOS,
                AccionSistema.GESTIONAR_SERVICIOS,
                AccionSistema.CONSULTAR_CUENTAS,
                AccionSistema.GESTIONAR_CUENTAS,
                AccionSistema.CONSULTAR_PAGOS,
                AccionSistema.GESTIONAR_PAGOS,
                AccionSistema.CONSULTAR_REPORTES,
            }
        ),
        Empleado.Area.SOPORTE: frozenset(
            {
                AccionSistema.CONSULTAR_CLIENTES,
                AccionSistema.CONSULTAR_SERVICIOS,
                AccionSistema.ATENDER_CONVERSACIONES,
                AccionSistema.CONSULTAR_TICKETS,
                AccionSistema.REGISTRAR_TICKETS,
                AccionSistema.GESTIONAR_TICKETS,
            }
        ),
        Empleado.Area.ATENCION_CLIENTE: frozenset(
            {
                AccionSistema.CONSULTAR_CLIENTES,
                AccionSistema.CONSULTAR_CUENTAS,
                AccionSistema.ATENDER_CONVERSACIONES,
                AccionSistema.CONSULTAR_TICKETS,
                AccionSistema.REGISTRAR_TICKETS,
            }
        ),
    }

    def __init__(self, area: str):
        """Inicializa la política con el área declarada para el empleado."""

        self.area = area

    def acciones_permitidas(self) -> frozenset[AccionSistema]:
        """Devuelve las acciones configuradas para el área del empleado."""

        return self.ACCIONES_POR_AREA.get(self.area, frozenset())


class ServicioAutorizacion:
    """Selecciona la política correspondiente y verifica el acceso solicitado."""

    def puede(self, usuario: Usuario, accion: AccionSistema) -> bool:
        """Autoriza únicamente usuarios activos con una especialización válida."""

        politica = self._obtener_politica(usuario)
        return politica is not None and politica.permite(accion)

    def acciones_permitidas(self, usuario: Usuario) -> frozenset[AccionSistema]:
        """Expone las acciones del usuario para construir menús o respuestas API."""

        politica = self._obtener_politica(usuario)
        return politica.acciones_permitidas() if politica else frozenset()

    def _obtener_politica(self, usuario: Usuario) -> PoliticaAcceso | None:
        """Obtiene la política y rechaza perfiles ausentes, dobles o inactivos."""

        if not getattr(usuario, "is_authenticated", False) or not usuario.is_active:
            return None

        es_administrador = Administrador.objects.filter(usuario=usuario).exists()
        empleado = Empleado.objects.filter(usuario=usuario).first()

        # La generalización es total y disjunta: exactamente un subtipo debe existir.
        if es_administrador == (empleado is not None):
            return None
        if es_administrador:
            return PoliticaAdministrador()
        return PoliticaEmpleado(empleado.area)
