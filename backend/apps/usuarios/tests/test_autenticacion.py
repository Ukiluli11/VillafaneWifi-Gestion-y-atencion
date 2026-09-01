"""Pruebas del inicio, protección y cierre de sesión definidos en RF-29."""

import pytest
from django.urls import reverse

from apps.usuarios.models import Usuario


@pytest.mark.django_db
class TestAutenticacion:
    """Comprueba el flujo principal de autenticación de usuarios internos."""

    def test_usuario_no_autenticado_es_redirigido(self, client):
        """Impide el acceso al panel cuando todavía no existe una sesión válida."""

        respuesta = client.get(reverse("usuarios:inicio"))

        assert respuesta.status_code == 302
        assert reverse("usuarios:iniciar-sesion") in respuesta.url

    def test_usuario_activo_puede_iniciar_sesion(self, client):
        """Permite ingresar con un nombre de usuario y una contraseña válidos."""

        Usuario.objects.create_user("operador", password="clave-segura-123")

        respuesta = client.post(
            reverse("usuarios:iniciar-sesion"),
            {"username": "operador", "password": "clave-segura-123"},
        )

        assert respuesta.status_code == 302
        assert respuesta.url == reverse("usuarios:inicio")

    def test_usuario_puede_cerrar_sesion(self, client):
        """Elimina la sesión cuando el usuario selecciona cerrar sesión."""

        usuario = Usuario.objects.create_user("operador", password="clave-segura-123")
        client.force_login(usuario)

        respuesta = client.post(reverse("usuarios:cerrar-sesion"))

        assert respuesta.status_code == 302
        assert respuesta.url == reverse("usuarios:iniciar-sesion")

    def test_usuario_inactivo_no_puede_iniciar_sesion(self, client):
        """Rechaza las credenciales de un usuario que fue desactivado."""

        Usuario.objects.create_user(
            "inactivo",
            password="clave-segura-123",
            is_active=False,
        )

        respuesta = client.post(
            reverse("usuarios:iniciar-sesion"),
            {"username": "inactivo", "password": "clave-segura-123"},
        )

        assert respuesta.status_code == 200
        assert "_auth_user_id" not in client.session
