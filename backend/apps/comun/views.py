"""Vistas HTTP transversales utilizadas para supervisar la aplicación."""

from django.http import JsonResponse
from django.views import View


class VistaSalud(View):
    """Expone una comprobación técnica del estado del backend."""

    def get(self, peticion):
        """Informa si el proceso está disponible para recibir solicitudes."""

        return JsonResponse({"estado": "correcto", "servicio": "villafane-wifi"})
