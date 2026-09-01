"""Formularios web para planes y conexiones contratadas."""

from django import forms
from django.db.models import Q

from apps.clientes.models import Cliente

from .models import Plan, Servicio
from .servicios import ServicioContrataciones, ServicioPlanes


class FormularioPlan(forms.ModelForm):
    """Permite crear o modificar los datos comerciales de un plan."""

    class Meta:
        """Declara los campos editables del catálogo."""

        model = Plan
        fields = ("nombre", "velocidad_mbps", "precio_vigente", "estado")

    def guardar(self) -> Plan:
        """Delega el alta o la edición al servicio de planes."""

        if self.instance.pk:
            return ServicioPlanes().actualizar(self.instance, self.cleaned_data)
        return ServicioPlanes().crear(self.cleaned_data)


class FormularioServicio(forms.ModelForm):
    """Permite crear o modificar una conexión de internet."""

    class Meta:
        """Declara los campos técnicos y comerciales editables."""

        model = Servicio
        fields = (
            "cliente",
            "plan",
            "instalacion_calle",
            "instalacion_numero",
            "instalacion_localidad",
            "dia_vencimiento",
            "fecha_alta",
            "ip",
            "mac",
            "estado",
        )
        widgets = {"fecha_alta": forms.DateInput(attrs={"type": "date"})}

    def __init__(self, *args, **kwargs):
        """Limita la selección a clientes y planes utilizables."""

        super().__init__(*args, **kwargs)
        filtro_cliente = Q(estado=Cliente.Estado.ACTIVO)
        filtro_plan = Q(estado=Plan.Estado.ACTIVO)
        if self.instance and self.instance.pk:
            filtro_cliente |= Q(pk=self.instance.cliente_id)
            filtro_plan |= Q(pk=self.instance.plan_id)
            self.fields["cliente"].disabled = True
        self.fields["cliente"].queryset = Cliente.objects.filter(filtro_cliente)
        self.fields["plan"].queryset = Plan.objects.filter(filtro_plan)

    def guardar(self) -> Servicio:
        """Delega la contratación o modificación a su servicio de dominio."""

        datos = dict(self.cleaned_data)
        cliente = datos.pop("cliente")
        if self.instance.pk:
            return ServicioContrataciones().actualizar(self.instance, datos)
        return ServicioContrataciones().crear(cliente, datos)
