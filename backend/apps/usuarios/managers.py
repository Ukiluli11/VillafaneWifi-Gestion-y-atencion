"""Gestores responsables de construir usuarios con credenciales seguras."""

from django.contrib.auth.base_user import BaseUserManager


class GestorUsuario(BaseUserManager):
    """Centraliza la creación normalizada de usuarios y superusuarios."""

    use_in_migrations = True

    def crear_usuario(self, nombre_usuario, contrasena=None, **campos_adicionales):
        """Crea un usuario y almacena su contraseña utilizando el hash de Django."""

        if not nombre_usuario:
            raise ValueError("El nombre de usuario es obligatorio.")
        usuario = self.model(nombre_usuario=nombre_usuario.strip(), **campos_adicionales)
        usuario.set_password(contrasena)
        usuario.save(using=self._db)
        return usuario

    def crear_superusuario(self, nombre_usuario, contrasena=None, **campos_adicionales):
        """Crea un usuario con permisos administrativos completos."""

        campos_adicionales.setdefault("is_staff", True)
        campos_adicionales.setdefault("is_superuser", True)
        campos_adicionales.setdefault("is_active", True)
        if not campos_adicionales["is_staff"] or not campos_adicionales["is_superuser"]:
            raise ValueError("El superusuario debe tener habilitados todos los permisos.")
        return self.crear_usuario(nombre_usuario, contrasena, **campos_adicionales)

    def create_user(self, nombre_usuario, password=None, **campos_adicionales):
        """Adapta la operación requerida por Django al método en español."""

        return self.crear_usuario(nombre_usuario, password, **campos_adicionales)

    def create_superuser(self, nombre_usuario, password=None, **campos_adicionales):
        """Adapta la creación de superusuarios requerida por Django."""

        return self.crear_superusuario(nombre_usuario, password, **campos_adicionales)

