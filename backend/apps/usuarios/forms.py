"""Formularios del panel para crear y modificar credenciales de usuarios."""

from django import forms
from django.contrib.auth.forms import ReadOnlyPasswordHashField

from .models import Usuario


class FormularioCreacionUsuario(forms.ModelForm):
    """Solicita y confirma la contraseña antes de crear un usuario."""

    contrasena1 = forms.CharField(label="Contraseña", widget=forms.PasswordInput)
    contrasena2 = forms.CharField(label="Confirmación", widget=forms.PasswordInput)

    class Meta:
        """Declara el modelo y los campos editables durante el alta."""

        model = Usuario
        fields = ("nombre_usuario",)

    def clean_contrasena2(self):
        """Comprueba que ambas contraseñas ingresadas coincidan."""

        contrasena1 = self.cleaned_data.get("contrasena1")
        contrasena2 = self.cleaned_data.get("contrasena2")
        if contrasena1 and contrasena2 and contrasena1 != contrasena2:
            raise forms.ValidationError("Las contraseñas no coinciden.")
        return contrasena2

    def save(self, commit=True):
        """Guarda el usuario utilizando el mecanismo seguro de hash de Django."""

        usuario = super().save(commit=False)
        usuario.set_password(self.cleaned_data["contrasena1"])
        if commit:
            usuario.save()
        return usuario


class FormularioCambioUsuario(forms.ModelForm):
    """Permite editar un usuario sin exponer su contraseña almacenada."""

    password = ReadOnlyPasswordHashField(label="Contraseña")

    class Meta:
        """Incluye los campos administrables del usuario existente."""

        model = Usuario
        fields = "__all__"

