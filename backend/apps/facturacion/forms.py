"""Formularios del panel para cuotas, pagos y cuentas receptoras."""

from django import forms
from django.utils import timezone

from apps.clientes.models import Cliente

from .models import CuentaReceptora, Cuota, Pago
from .servicios import ServicioCuentaCorriente, ServicioCuentasReceptoras, ServicioFacturacion


class FormularioPeriodo(forms.Form):
    """Solicita el mes que se facturará a todos los servicios activos."""

    periodo = forms.CharField(
        label="Período",
        max_length=7,
        help_text="Formato AAAA-MM, por ejemplo 2026-09.",
        widget=forms.TextInput(attrs={"placeholder": "AAAA-MM"}),
    )

    def __init__(self, *args, **kwargs):
        """Propone el mes actual como período inicial de facturación."""

        super().__init__(*args, **kwargs)
        if not self.is_bound:
            self.initial["periodo"] = timezone.localdate().strftime("%Y-%m")

    def guardar(self) -> int:
        """Genera las cuotas faltantes y devuelve cuántas fueron creadas."""

        return ServicioFacturacion().generar_para_servicios_activos(self.cleaned_data["periodo"])


class FormularioCuentaReceptora(forms.ModelForm):
    """Permite dar de alta una cuenta donde la empresa recibe dinero."""

    class Meta:
        """Declara los datos editables de una cuenta receptora."""

        model = CuentaReceptora
        fields = ("nombre", "tipo", "identificador", "estado")

    def guardar(self) -> CuentaReceptora:
        """Delega el alta al servicio correspondiente."""

        return ServicioCuentasReceptoras().crear(self.cleaned_data)


class FormularioRegistroPago(forms.Form):
    """Registra un pago seleccionando una o varias cuotas completas."""

    cuotas = forms.ModelMultipleChoiceField(
        label="Cuotas a cancelar",
        queryset=Cuota.objects.none(),
        widget=forms.CheckboxSelectMultiple,
        help_text="El importe será la suma exacta de las cuotas seleccionadas.",
    )
    cuenta = forms.ModelChoiceField(
        label="Cuenta receptora",
        queryset=CuentaReceptora.objects.none(),
    )
    medio_pago = forms.ChoiceField(label="Medio de pago", choices=Pago.Medio.choices)
    fecha = forms.DateTimeField(
        label="Fecha y hora",
        widget=forms.DateTimeInput(attrs={"type": "datetime-local"}),
        input_formats=("%Y-%m-%dT%H:%M",),
    )

    def __init__(self, *args, cliente: Cliente, **kwargs):
        """Limita las cuotas y cuentas a opciones válidas para el registro."""

        super().__init__(*args, **kwargs)
        self.cliente = cliente
        self.fields["cuotas"].queryset = ServicioCuentaCorriente().cuotas_pendientes(cliente)
        self.fields["cuenta"].queryset = CuentaReceptora.objects.filter(
            estado=CuentaReceptora.Estado.ACTIVA
        )
        if not self.is_bound:
            self.initial["fecha"] = timezone.localtime().strftime("%Y-%m-%dT%H:%M")

    def guardar(self) -> Pago:
        """Delega el pago atómico al servicio de facturación."""

        return ServicioFacturacion().registrar_pago(
            cuotas=self.cleaned_data["cuotas"],
            cuenta=self.cleaned_data["cuenta"],
            medio_pago=self.cleaned_data["medio_pago"],
            fecha=self.cleaned_data["fecha"],
        )
