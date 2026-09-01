"""Mixins reutilizables por formularios y vistas de distintos módulos."""


class ManejoErroresDominioMixin:
    """Distribuye errores de dominio entre los campos de un formulario web."""

    def agregar_error_dominio(self, formulario, error):
        """Agrega cada mensaje al campo correspondiente o al formulario general."""

        errores = getattr(error, "message_dict", None)
        if errores:
            for campo, mensajes in errores.items():
                destino = campo if campo in formulario.fields else None
                for mensaje in mensajes:
                    formulario.add_error(destino, mensaje)
            return
        for mensaje in error.messages:
            formulario.add_error(None, mensaje)
