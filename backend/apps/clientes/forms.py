"""Formularios web para el alta y la edición de clientes."""

import re

from django import forms

from apps.servicios.models import Plan

from .casos_uso import CasoUsoAltaIntegralCliente
from .models import Cliente
from .servicios import ServicioClientes


class FormularioAltaCliente(forms.Form):
    """Reúne los datos del cliente y su primera conexión en una sola pantalla."""

    tipo_documento = forms.ChoiceField(
        label="Tipo de documento",
        choices=Cliente.TipoDocumento.choices,
    )
    numero_documento = forms.CharField(label="Número de documento", max_length=30)
    nombre_razon_social = forms.CharField(label="Nombre o razón social", max_length=160)
    tipo_cliente = forms.ChoiceField(label="Tipo de cliente", choices=Cliente.TipoCliente.choices)
    contacto_calle = forms.CharField(label="Calle de contacto", max_length=120)
    contacto_numero = forms.CharField(
        label="Número",
        max_length=20,
        required=False,
    )
    contacto_localidad = forms.CharField(label="Localidad", max_length=100)
    telefono_principal = forms.CharField(label="Teléfono o WhatsApp", max_length=30)
    telefono_alternativo = forms.CharField(
        label="Teléfono alternativo",
        max_length=30,
        required=False,
    )
    plan = forms.ModelChoiceField(label="Plan contratado", queryset=Plan.objects.none())
    instalacion_calle = forms.CharField(label="Calle de instalación", max_length=120)
    instalacion_numero = forms.CharField(
        label="Número de instalación",
        max_length=20,
        required=False,
    )
    instalacion_localidad = forms.CharField(label="Localidad de instalación", max_length=100)
    dia_vencimiento = forms.IntegerField(label="Día de vencimiento", min_value=1, max_value=31)
    ip = forms.GenericIPAddressField(label="Dirección IP", required=False)
    mac = forms.CharField(label="Dirección MAC", max_length=17, required=False)

    def __init__(self, *args, **kwargs):
        """Carga únicamente planes activos y prepara ayudas visuales."""

        super().__init__(*args, **kwargs)
        self.fields["plan"].queryset = Plan.objects.filter(estado=Plan.Estado.ACTIVO)
        self.fields["ip"].widget.attrs["placeholder"] = "172.138.1.20"
        self.fields["mac"].widget.attrs["placeholder"] = "AA:BB:CC:DD:EE:FF"

    def guardar(self) -> Cliente:
        """Ejecuta el alta integral utilizando los datos ya validados."""

        datos = self.cleaned_data
        telefonos = [datos["telefono_principal"]]
        if datos.get("telefono_alternativo"):
            telefonos.append(datos["telefono_alternativo"])
        datos_cliente = {
            campo: datos[campo]
            for campo in (
                "tipo_documento",
                "numero_documento",
                "nombre_razon_social",
                "tipo_cliente",
                "contacto_calle",
                "contacto_numero",
                "contacto_localidad",
            )
        }
        datos_servicio = {
            "id_plan": datos["plan"].pk,
            "instalacion_calle": datos["instalacion_calle"],
            "instalacion_numero": datos["instalacion_numero"],
            "instalacion_localidad": datos["instalacion_localidad"],
            "dia_vencimiento": datos["dia_vencimiento"],
            "ip": datos.get("ip"),
            "mac": datos.get("mac"),
        }
        return CasoUsoAltaIntegralCliente().ejecutar(
            datos_cliente,
            telefonos,
            [datos_servicio],
        )


class FormularioEdicionCliente(forms.ModelForm):
    """Edita los datos de contacto y uno o más teléfonos del cliente."""

    telefonos = forms.CharField(
        label="Teléfonos o WhatsApp",
        help_text="Separá varios números con comas o saltos de línea.",
        widget=forms.Textarea(attrs={"rows": 3}),
    )

    class Meta:
        """Declara los campos editables del cliente."""

        model = Cliente
        fields = (
            "tipo_documento",
            "numero_documento",
            "nombre_razon_social",
            "tipo_cliente",
            "contacto_calle",
            "contacto_numero",
            "contacto_localidad",
        )

    def __init__(self, *args, **kwargs):
        """Presenta los teléfonos existentes en un formato fácil de editar."""

        super().__init__(*args, **kwargs)
        if self.instance and self.instance.pk:
            self.fields["telefonos"].initial = ", ".join(
                self.instance.telefonos.values_list("numero", flat=True)
            )

    def clean_telefonos(self):
        """Transforma el texto ingresado en una lista de contactos."""

        contenido = self.cleaned_data["telefonos"]
        return [numero.strip() for numero in re.split(r"[,\n]+", contenido) if numero.strip()]

    def guardar(self) -> Cliente:
        """Delega la edición validada al servicio de clientes."""

        datos = {
            campo: self.cleaned_data[campo]
            for campo in self.Meta.fields
        }
        return ServicioClientes().actualizar(
            self.instance,
            datos,
            self.cleaned_data["telefonos"],
        )
