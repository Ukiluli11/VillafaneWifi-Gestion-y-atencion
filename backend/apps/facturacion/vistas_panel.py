"""Vistas HTML de cuenta corriente, facturación y cobranzas."""

from django.contrib import messages
from django.core.exceptions import ValidationError
from django.shortcuts import get_object_or_404, redirect
from django.views.generic import FormView, ListView, TemplateView

from apps.clientes.models import Cliente
from apps.comun.mixins import ManejoErroresDominioMixin
from apps.usuarios.mixins import AccionRequeridaMixin
from apps.usuarios.politicas import AccionSistema

from .forms import FormularioCuentaReceptora, FormularioPeriodo, FormularioRegistroPago
from .models import CuentaReceptora
from .servicios import ServicioCuentaCorriente


class VistaListaCuentasCorrientes(AccionRequeridaMixin, TemplateView):
    """Lista clientes y resume la situación de cada cuenta corriente."""

    accion_requerida = AccionSistema.CONSULTAR_CUENTAS
    template_name = "facturacion/lista_cuentas.html"

    def get_context_data(self, **kwargs):
        """Construye los resúmenes calculados y conserva el texto buscado."""

        contexto = super().get_context_data(**kwargs)
        buscar = self.request.GET.get("buscar", "").strip()
        servicio = ServicioCuentaCorriente()
        clientes = servicio.buscar_clientes_con_cuenta(buscar)
        contexto["cuentas"] = [(cliente, servicio.resumir(cliente)) for cliente in clientes]
        contexto["buscar"] = buscar
        return contexto


class VistaDetalleCuentaCorriente(AccionRequeridaMixin, TemplateView):
    """Presenta deuda, cuotas e historial de pagos de un cliente."""

    accion_requerida = AccionSistema.CONSULTAR_CUENTAS
    template_name = "facturacion/detalle_cuenta.html"

    def obtener_cliente(self) -> Cliente:
        """Recupera el titular indicado en la ruta."""

        return get_object_or_404(Cliente, pk=self.kwargs["pk"])

    def get_context_data(self, **kwargs):
        """Incluye todos los datos calculados necesarios para RF-05 y RF-06."""

        contexto = super().get_context_data(**kwargs)
        cliente = self.obtener_cliente()
        servicio = ServicioCuentaCorriente()
        contexto.update(
            cliente=cliente,
            resumen=servicio.resumir(cliente),
            cuotas=servicio.cuotas_del_cliente(cliente),
            pagos=servicio.pagos_del_cliente(cliente),
            hay_cuentas_activas=CuentaReceptora.objects.filter(
                estado=CuentaReceptora.Estado.ACTIVA
            ).exists(),
        )
        return contexto


class VistaRegistroPago(ManejoErroresDominioMixin, AccionRequeridaMixin, FormView):
    """Permite cancelar varias cuotas completas con una misma transferencia."""

    accion_requerida = AccionSistema.GESTIONAR_PAGOS
    template_name = "facturacion/formulario_pago.html"
    form_class = FormularioRegistroPago

    def obtener_cliente(self) -> Cliente:
        """Obtiene el cliente cuya deuda será cancelada."""

        return get_object_or_404(Cliente, pk=self.kwargs["pk"])

    def get_form_kwargs(self):
        """Entrega el cliente al formulario para limitar las cuotas elegibles."""

        argumentos = super().get_form_kwargs()
        argumentos["cliente"] = self.obtener_cliente()
        return argumentos

    def get_context_data(self, **kwargs):
        """Muestra el titular junto con el formulario de cobranza."""

        contexto = super().get_context_data(**kwargs)
        contexto["cliente"] = self.obtener_cliente()
        return contexto

    def form_valid(self, form):
        """Registra el pago y vuelve a la cuenta del cliente."""

        try:
            pago = form.guardar()
        except ValidationError as error:
            self.agregar_error_dominio(form, error)
            return self.form_invalid(form)
        messages.success(self.request, f"El pago #{pago.pk} fue registrado correctamente.")
        return redirect("facturacion-panel:detalle", pk=self.kwargs["pk"])


class VistaGeneracionCuotas(ManejoErroresDominioMixin, AccionRequeridaMixin, FormView):
    """Genera en bloque las cuotas mensuales faltantes."""

    accion_requerida = AccionSistema.GESTIONAR_CUENTAS
    template_name = "facturacion/generar_cuotas.html"
    form_class = FormularioPeriodo

    def form_valid(self, form):
        """Ejecuta la facturación mensual y comunica el resultado."""

        try:
            cantidad = form.guardar()
        except ValidationError as error:
            self.agregar_error_dominio(form, error)
            return self.form_invalid(form)
        messages.success(self.request, f"Se generaron {cantidad} cuotas nuevas.")
        return redirect("facturacion-panel:lista")


class VistaListaCuentasReceptoras(AccionRequeridaMixin, ListView):
    """Muestra las cuentas en las que se acreditan pagos."""

    accion_requerida = AccionSistema.GESTIONAR_PAGOS
    template_name = "facturacion/lista_receptoras.html"
    context_object_name = "cuentas_receptoras"
    queryset = CuentaReceptora.objects.all()


class VistaAltaCuentaReceptora(
    ManejoErroresDominioMixin,
    AccionRequeridaMixin,
    FormView,
):
    """Permite registrar una nueva cuenta receptora."""

    accion_requerida = AccionSistema.GESTIONAR_PAGOS
    template_name = "facturacion/formulario_receptora.html"
    form_class = FormularioCuentaReceptora

    def form_valid(self, form):
        """Crea la cuenta o muestra sus validaciones de negocio."""

        try:
            form.guardar()
        except ValidationError as error:
            self.agregar_error_dominio(form, error)
            return self.form_invalid(form)
        messages.success(self.request, "La cuenta receptora fue registrada.")
        return redirect("facturacion-panel:cuentas-receptoras")
