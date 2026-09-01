"""Vistas HTTP transversales utilizadas para supervisar la aplicación."""

from django.http import JsonResponse


def verificar_salud(peticion):
    """Informa si el proceso del backend está disponible para recibir solicitudes."""

    return JsonResponse({"estado": "correcto", "servicio": "villafane-wifi"})

