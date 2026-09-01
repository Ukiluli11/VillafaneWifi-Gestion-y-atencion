"""Vistas HTML del panel para administrar planes y conexiones."""

from django.contrib import messages
from django.core.exceptions import ValidationError
from django.shortcuts import get_object_or_404, redirect
from django.views.generic import FormView, ListView, TemplateView, UpdateView

from apps.comun.mixins import ManejoErroresDominioMixin
from apps.usuarios.mixins import AccionRequeridaMixin
from apps.usuarios.politicas import AccionSistema

from .forms import FormularioPlan, FormularioServicio
from .models import Plan, Servicio
from .servicios import ServicioContrataciones, ServicioPlanes


class VistaListaPlanes(AccionRequeridaMixin, ListView):
    """Muestra el catálogo con velocidad, precio y estado."""

    accion_requerida = AccionSistema.CONSULTAR_PLANES
    template_name = "servicios/lista_planes.html"
    context_object_name = "planes"
    queryset = Plan.objects.all()


class VistaAltaPlan(ManejoErroresDominioMixin, AccionRequeridaMixin, FormView):
    """Permite agregar una opción al catálogo comercial."""

    accion_requerida = AccionSistema.GESTIONAR_PLANES
    template_name = "servicios/formulario_plan.html"
    form_class = FormularioPlan

    def form_valid(self, form):
        """Crea el plan o devuelve los errores de negocio al formulario."""

        try:
            form.guardar()
        except ValidationError as error:
            self.agregar_error_dominio(form, error)
            return self.form_invalid(form)
        messages.success(self.request, "El plan fue creado correctamente.")
        return redirect("servicios-panel:planes")


class VistaEdicionPlan(ManejoErroresDominioMixin, AccionRequeridaMixin, UpdateView):
    """Permite actualizar las condiciones vigentes de un plan."""

    accion_requerida = AccionSistema.GESTIONAR_PLANES
    template_name = "servicios/formulario_plan.html"
    form_class = FormularioPlan
    model = Plan

    def form_valid(self, form):
        """Actualiza el plan mediante el servicio de dominio."""

        try:
            form.guardar()
        except ValidationError as error:
            self.agregar_error_dominio(form, error)
            return self.form_invalid(form)
        messages.success(self.request, "El plan fue actualizado.")
        return redirect("servicios-panel:planes")


class VistaBajaPlan(AccionRequeridaMixin, TemplateView):
    """Confirma la inactivación de un plan del catálogo."""

    accion_requerida = AccionSistema.GESTIONAR_PLANES
    template_name = "servicios/confirmar_baja_plan.html"

    def obtener_plan(self):
        """Recupera el plan solicitado o responde con error 404."""

        return get_object_or_404(Plan, pk=self.kwargs["pk"])

    def get_context_data(self, **kwargs):
        """Incluye el plan dentro de la pantalla de confirmación."""

        contexto = super().get_context_data(**kwargs)
        contexto["plan"] = self.obtener_plan()
        return contexto

    def post(self, request, *args, **kwargs):
        """Realiza la baja lógica después de la confirmación."""

        ServicioPlanes().dar_de_baja(self.obtener_plan())
        messages.success(request, "El plan fue dado de baja.")
        return redirect("servicios-panel:planes")


class VistaListaServicios(AccionRequeridaMixin, ListView):
    """Muestra las conexiones contratadas y permite filtrar por cliente."""

    accion_requerida = AccionSistema.CONSULTAR_SERVICIOS
    template_name = "servicios/lista_servicios.html"
    context_object_name = "servicios"
    paginate_by = 20

    def get_queryset(self):
        """Devuelve conexiones con cliente y plan precargados."""

        consulta = Servicio.objects.select_related("cliente", "plan").all()
        cliente = self.request.GET.get("id_cliente")
        if cliente:
            consulta = consulta.filter(cliente_id=cliente)
        return consulta


class VistaAltaServicio(ManejoErroresDominioMixin, AccionRequeridaMixin, FormView):
    """Permite contratar otra conexión para un cliente existente."""

    accion_requerida = AccionSistema.GESTIONAR_SERVICIOS
    template_name = "servicios/formulario_servicio.html"
    form_class = FormularioServicio

    def get_initial(self):
        """Preselecciona el cliente cuando llega desde su ficha."""

        inicial = super().get_initial()
        if self.request.GET.get("id_cliente"):
            inicial["cliente"] = self.request.GET["id_cliente"]
        return inicial

    def form_valid(self, form):
        """Registra la conexión o muestra sus validaciones de dominio."""

        try:
            servicio = form.guardar()
        except ValidationError as error:
            self.agregar_error_dominio(form, error)
            return self.form_invalid(form)
        messages.success(self.request, "El servicio fue contratado correctamente.")
        return redirect("clientes-panel:detalle", pk=servicio.cliente_id)


class VistaEdicionServicio(ManejoErroresDominioMixin, AccionRequeridaMixin, UpdateView):
    """Permite modificar los datos de una conexión existente."""

    accion_requerida = AccionSistema.GESTIONAR_SERVICIOS
    template_name = "servicios/formulario_servicio.html"
    form_class = FormularioServicio
    model = Servicio

    def form_valid(self, form):
        """Actualiza la conexión y regresa a la ficha del cliente."""

        try:
            servicio = form.guardar()
        except ValidationError as error:
            self.agregar_error_dominio(form, error)
            return self.form_invalid(form)
        messages.success(self.request, "El servicio fue actualizado.")
        return redirect("clientes-panel:detalle", pk=servicio.cliente_id)


class VistaBajaServicio(AccionRequeridaMixin, TemplateView):
    """Confirma y realiza la baja lógica de una conexión."""

    accion_requerida = AccionSistema.GESTIONAR_SERVICIOS
    template_name = "servicios/confirmar_baja_servicio.html"

    def obtener_servicio(self):
        """Recupera la conexión indicada o responde con error 404."""

        return get_object_or_404(
            Servicio.objects.select_related("cliente", "plan"),
            pk=self.kwargs["pk"],
        )

    def get_context_data(self, **kwargs):
        """Incluye la conexión dentro de la confirmación."""

        contexto = super().get_context_data(**kwargs)
        contexto["servicio"] = self.obtener_servicio()
        return contexto

    def post(self, request, *args, **kwargs):
        """Inactiva la conexión y vuelve a la ficha del cliente."""

        servicio = self.obtener_servicio()
        ServicioContrataciones().dar_de_baja(servicio)
        messages.success(request, "El servicio fue dado de baja.")
        return redirect("clientes-panel:detalle", pk=servicio.cliente_id)
