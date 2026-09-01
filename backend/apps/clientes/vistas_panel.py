"""Vistas HTML del panel para consultar y gestionar clientes."""

from django.contrib import messages
from django.core.exceptions import ValidationError
from django.shortcuts import get_object_or_404, redirect
from django.views.generic import DetailView, FormView, ListView, TemplateView, UpdateView

from apps.comun.mixins import ManejoErroresDominioMixin
from apps.servicios.models import Plan
from apps.usuarios.mixins import AccionRequeridaMixin
from apps.usuarios.politicas import AccionSistema

from .forms import FormularioAltaCliente, FormularioEdicionCliente
from .models import Cliente
from .servicios import ServicioClientes


class VistaListaClientes(AccionRequeridaMixin, ListView):
    """Muestra clientes y permite buscarlos por los criterios de RF-03."""

    accion_requerida = AccionSistema.CONSULTAR_CLIENTES
    template_name = "clientes/lista.html"
    context_object_name = "clientes"
    paginate_by = 20

    def get_queryset(self):
        """Aplica la búsqueda solicitada desde el panel."""

        return ServicioClientes().buscar(self.request.GET.get("buscar", ""))

    def get_context_data(self, **kwargs):
        """Conserva el texto buscado y la acción de gestión en la plantilla."""

        contexto = super().get_context_data(**kwargs)
        contexto["buscar"] = self.request.GET.get("buscar", "").strip()
        return contexto


class VistaDetalleCliente(AccionRequeridaMixin, DetailView):
    """Presenta datos, teléfonos y servicios de un cliente."""

    accion_requerida = AccionSistema.CONSULTAR_CLIENTES
    template_name = "clientes/detalle.html"
    context_object_name = "cliente"
    queryset = ServicioClientes().listar()


class VistaAltaCliente(ManejoErroresDominioMixin, AccionRequeridaMixin, FormView):
    """Presenta y procesa el alta integral de RF-01."""

    accion_requerida = AccionSistema.GESTIONAR_CLIENTES
    template_name = "clientes/formulario_alta.html"
    form_class = FormularioAltaCliente

    def get_context_data(self, **kwargs):
        """Indica si existe un plan activo requerido para completar el alta."""

        contexto = super().get_context_data(**kwargs)
        contexto["hay_planes_activos"] = Plan.objects.filter(estado=Plan.Estado.ACTIVO).exists()
        return contexto

    def form_valid(self, form):
        """Crea el cliente y redirige a su ficha si toda la operación es válida."""

        try:
            cliente = form.guardar()
        except ValidationError as error:
            self.agregar_error_dominio(form, error)
            return self.form_invalid(form)
        messages.success(self.request, "El cliente fue registrado correctamente.")
        return redirect("clientes-panel:detalle", pk=cliente.pk)


class VistaEdicionCliente(ManejoErroresDominioMixin, AccionRequeridaMixin, UpdateView):
    """Permite modificar datos y contactos sin alterar los servicios."""

    accion_requerida = AccionSistema.GESTIONAR_CLIENTES
    template_name = "clientes/formulario_edicion.html"
    form_class = FormularioEdicionCliente
    model = Cliente
    context_object_name = "cliente"

    def form_valid(self, form):
        """Guarda la edición mediante las reglas del servicio de clientes."""

        try:
            cliente = form.guardar()
        except ValidationError as error:
            self.agregar_error_dominio(form, error)
            return self.form_invalid(form)
        messages.success(self.request, "Los datos del cliente fueron actualizados.")
        return redirect("clientes-panel:detalle", pk=cliente.pk)


class VistaBajaCliente(AccionRequeridaMixin, TemplateView):
    """Solicita confirmación y ejecuta la baja lógica del cliente."""

    accion_requerida = AccionSistema.GESTIONAR_CLIENTES
    template_name = "clientes/confirmar_baja.html"

    def obtener_cliente(self):
        """Recupera el cliente indicado en la ruta o responde con error 404."""

        return get_object_or_404(Cliente, pk=self.kwargs["pk"])

    def get_context_data(self, **kwargs):
        """Incluye el cliente que se mostrará en la confirmación."""

        contexto = super().get_context_data(**kwargs)
        contexto["cliente"] = self.obtener_cliente()
        return contexto

    def post(self, request, *args, **kwargs):
        """Inactiva al cliente después de recibir la confirmación."""

        ServicioClientes().dar_de_baja(self.obtener_cliente())
        messages.success(request, "El cliente y sus servicios fueron dados de baja.")
        return redirect("clientes-panel:lista")
