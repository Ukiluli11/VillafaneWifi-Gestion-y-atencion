# Matriz de Trazabilidad Total: Casos de Uso ↔ Diagrama de Clases ↔ Código Fuente

> **Proyecto:** Sistema de Gestión y Atención — Villafañe Wifi  
> **Propósito:** Guía de referencia rápida ante consultas de cátedra/profesor para ubicar instantáneamente la correlación exacta 1:1 entre:
> 1. El **Diagrama de Casos de Uso** (nombre exacto en el óvalo del diagrama)
> 2. El **Diagrama de Clases** (clase y método en el diagrama UML)
> 3. El **Código Fuente PHP** (archivo de clase y función implementada)

---

## 1. Módulo 1 — Gestión de Clientes, Servicios, Planes y Cuotas

| Caso de Uso (Diagrama CU) | Actor | Clase UML | Método UML | Archivo PHP | Función en Código | Descripción breve |
|---|---|---|---|---|---|---|
| **Dar de alta cliente y servicio** | Empleado / Administrador | `Cliente` | `+ darDeAlta() : void` | `app/Models/Cliente.php`<br>`app/Dominio/ServicioClientes.php` | `Cliente::darDeAlta()`<br>`ServicioClientes::crear()` | Da de alta al cliente en estado activo e inicializa su instalación técnica. |
| **Dar de baja cliente o servicio** | Empleado / Administrador | `Cliente`<br>`Servicio` | `+ darDeBaja() : void`<br>`+ suspender() : void` | `app/Models/Cliente.php`<br>`app/Models/Servicio.php` | `Cliente::darDeBaja()`<br>`Servicio::suspender()` | Da de baja al abonado y suspende la prestación técnica de su conexión. |
| **Suspender servicio** | Reloj del Sistema / Admin | `Servicio` | `+ suspender() : void` | `app/Models/Servicio.php`<br>`app/Dominio/ServicioContrataciones.php` | `Servicio::suspender()`<br>`ServicioContrataciones::suspender()` | Interrumpe temporalmente la conexión por mora o solicitud operativa. |
| **Reactivar servicio** | Empleado / Administrador | `Servicio` | `+ reactivar() : void` | `app/Models/Servicio.php`<br>`app/Dominio/ServicioContrataciones.php` | `Servicio::reactivar()`<br>`ServicioContrataciones::reactivar()` | Restablece el servicio activo tras regularizarse los pagos del cliente. |
| **Configurar planes** | Administrador | `Administrador` | `+ configurarPlanes() : void` | `app/Models/Administrador.php`<br>`app/Http/Controllers/Web/PlanController.php` | `Administrador::configurarPlanes()`<br>`PlanController::index()` | Permite al administrador gestionar el catálogo tarifario de conectividad. |
| **Registrar y activar plan** | Administrador | `Plan` | `+ activar() : void` | `app/Models/Plan.php`<br>`app/Dominio/ServicioPlanes.php` | `Plan::activar()`<br>`ServicioPlanes::crear()` | Da de alta una nueva propuesta de velocidad y precio habilitándola para venta. |
| **Cambiar precio de plan** | Administrador | `Plan` | `+ cambiarPrecio(nuevoPrecio: BigDecimal) : void` | `app/Models/Plan.php` | `Plan::cambiarPrecio($nuevoPrecio)` | Actualiza el valor monetario del abono mensual para futuras facturaciones. |
| **Desactivar plan** | Administrador | `Plan` | `+ desactivar() : void` | `app/Models/Plan.php`<br>`app/Dominio/ServicioPlanes.php` | `Plan::desactivar()`<br>`ServicioPlanes::desactivar()` | Da de baja el plan del catálogo para impedir nuevas contrataciones. |
| **Generar cuotas mensuales** | Reloj del Sistema / Empleado | `Cuota`<br>`ServicioFacturacion` | `+ marcarComoVencida() : void` | `app/Dominio/ServicioFacturacion.php`<br>`app/Http/Controllers/Web/CuotaController.php` | `ServicioFacturacion::generarParaServiciosActivos()` | Emite masivamente las cuotas del período para todas las conexiones activas. |
| **Marcar cuotas vencidas** | Reloj del Sistema | `Cuota` | `+ marcarComoVencida() : void` | `app/Models/Cuota.php` | `Cuota::marcarComoVencida()` | Actualiza el estado a vencida al expirar la fecha límite sin pago imputado. |
| **Gestionar cuentas receptoras** | Administrador | `CuentaReceptora` | `+ activar() : void`<br>`+ desactivar() : void` | `app/Models/CuentaReceptora.php`<br>`app/Http/Controllers/Web/CuentaReceptoraController.php` | `CuentaReceptora::activar()`<br>`CuentaReceptoraController::index()` | Administra las cuentas bancarias y billeteras virtuales habilitadas para cobro. |
| **Registrar y activar cuenta** | Administrador | `CuentaReceptora` | `+ activar() : void` | `app/Models/CuentaReceptora.php`<br>`app/Http/Controllers/Web/CuentaReceptoraController.php` | `CuentaReceptora::activar()`<br>`CuentaReceptoraController::store()` | Da de alta una cuenta con CBU/CVU y la habilita para recibir transferencias. |
| **Desactivar cuenta receptora** | Administrador | `CuentaReceptora` | `+ desactivar() : void` | `app/Models/CuentaReceptora.php` | `CuentaReceptora::desactivar()` | Deshabilita una cuenta bancaria discontinuada para no mostrarla a clientes. |
| **Buscar cliente** | Empleado / Administrador | `Cliente` | `Scope / Query` | `app/Dominio/ServicioClientes.php`<br>`app/Http/Controllers/Web/ClienteController.php` | `ServicioClientes::buscar()` | Localiza clientes por nombre, apellido, DNI o número de teléfono. |
| **Consultar cuenta corriente** | Empleado / Administrador | `ServicioCuentaCorriente` | `+ obtenerEstadoCuenta()` | `app/Dominio/ServicioCuentaCorriente.php` | `ServicioCuentaCorriente::obtenerEstadoCuenta()` | Consolida cuotas adeudadas, pagos imputados y saldo total del abonado. |

---

## 2. Módulo 2 — Atención y Asistencia por WhatsApp

| Caso de Uso (Diagrama CU) | Actor | Clase UML | Método UML | Archivo PHP | Función en Código | Descripción breve |
|---|---|---|---|---|---|---|
| **Atender conversación por WhatsApp** | Cliente / Bot / Empleado | `WhatsAppService`<br>`Empleado` | `+ enviarMensaje(...)`<br>`+ atenderConversacion(...)` | `app/Dominio/WhatsAppService.php`<br>`app/Models/Empleado.php` | `WhatsAppService::enviarMensaje()`<br>`Empleado::atenderConversacion()` | Flujo omnicanal de intercambio de mensajes con el cliente. |
| **Procesar mensaje entrante** | WhatsApp API / Webhook | `Mensaje`<br>`WhatsAppService` | `+ marcarEnviado(id) : void`<br>`+ extraerMensajes(payload)` | `app/Models/Mensaje.php`<br>`app/Dominio/WhatsAppService.php` | `Mensaje::marcarEnviado()`<br>`WhatsAppService::extraerMensajes()` | Recibe y parsea el webhook de Meta y registra el mensaje entrante. |
| **Abrir o reutilizar conversación** | Cliente / Bot | `Conversacion` | `+ iniciar() : void` | `app/Models/Conversacion.php` | `Conversacion::iniciar()` | Abre una sesión conversacional activa registrando la marca de tiempo inicial. |
| **Registrar mensaje en el historial** | Bot / Cliente / Empleado | `Conversacion` | `+ agregarMensaje(m) : void` | `app/Models/Conversacion.php` | `Conversacion::agregarMensaje()` | Persiste cada mensaje entrante o saliente vinculándolo a la sesión. |
| **Identificar cliente por teléfono o DNI** | Bot / Sistema | `ServicioIdentificacion` | `+ identificarCliente(...)` | `app/Dominio/ServicioIdentificacion.php` | `ServicioIdentificacion::identificarCliente()` | Vincula el número remitente o el DNI ingresado con el registro del Cliente. |
| **Reconocer intención mediante IA** | Bot / Sistema | `IAService` | `+ interpretarIntencion(texto, ctx) : Object` | `app/Dominio/IAService.php` | `IAService::interpretarIntencion()` | Invoca el LLM de OpenAI para clasificar el trámite o reclamo del cliente. |
| **Solicitar reformulación del mensaje** | Bot / Sistema | `Conversacion` | `+ registrarIntentoFallido() : void` | `app/Models/Conversacion.php` | `Conversacion::registrarIntentoFallido()` | Solicita al cliente reformular su consulta ante intenciones no comprendidas. |
| **Derivar tras segundo fallo de IA** | Bot / Sistema | `Conversacion` | `+ escalar() : void` | `app/Models/Conversacion.php` | `Conversacion::escalar()` | Cambia el modo de atención a humano y encola la sesión para un operador. |
| **Tomar control de conversación escalada** | Empleado | `Empleado`<br>`Conversacion` | `+ atenderConversacion(c) : void`<br>`+ derivarAUsuario(u) : void` | `app/Models/Empleado.php`<br>`app/Models/Conversacion.php` | `Empleado::atenderConversacion()`<br>`Conversacion::derivarAUsuario()` | El operador toma a su cargo la conversación y envía mensaje de saludo. |
| **Consultar historial de conversación** | Empleado / Administrador | `Conversacion` | `+ obtenerHistorial() : List<Mensaje>` | `app/Models/Conversacion.php` | `Conversacion::obtenerHistorial()` | Devuelve cronológicamente los mensajes previos para auditoría o soporte. |
| **Cerrar conversación resuelta por el bot** | Bot / Sistema | `Conversacion` | `+ cerrar() : void` | `app/Models/Conversacion.php` | `Conversacion::cerrar()` | Concluye la interacción y registra la fecha de cierre de la sesión. |
| **Cerrar atención escalada** | Empleado | `Conversacion` | `+ cerrar() : void` | `app/Models/Conversacion.php` | `Conversacion::cerrar()` | El operador finaliza la conversación tras resolver la consulta humana. |
| **Registrar reclamo o soporte técnico** | Bot / Empleado | `Ticket` | `+ resolver() : void`<br>`+ cerrar() : void` | `app/Models/Ticket.php`<br>`app/Dominio/ServicioTickets.php` | `Ticket::resolver()`<br>`ServicioTickets::crearDesdeConversacion()` | Registra un incidente técnico vinculado al servicio del cliente. |
| **Consultar tickets asignados** | Empleado | `Empleado` | `+ consultarTicketsAsignados() : List<Ticket>` | `app/Models/Empleado.php` | `Empleado::consultarTicketsAsignados()` | Obtiene la lista de reclamos técnicos asignados al operador en turno. |
| **Agregar nota interna a ticket** | Empleado / Administrador | `Ticket`<br>`NotaInterna` | `+ agregarNota(txt, u) : NotaInterna`<br>`+ registrar() : void` | `app/Models/Ticket.php`<br>`app/Models/NotaInterna.php` | `Ticket::agregarNota()`<br>`NotaInterna::registrar()` | Redacta y persiste una anotación técnica interna en el ticket. |
| **Notificar vencimientos por WhatsApp** | Reloj del Sistema (Cron) | `ServicioNotificacionesVencimiento` | `+ enviar() : Object` | `app/Dominio/ServicioNotificacionesVencimiento.php` | `ServicioNotificacionesVencimiento::enviar()` | Despacha alertas preventivas automáticas de cuotas próximas a vencer. |

---

## 3. Módulo 3 — Conciliación de Pagos y Comprobantes

| Caso de Uso (Diagrama CU) | Actor | Clase UML | Método UML | Archivo PHP | Función en Código | Descripción breve |
|---|---|---|---|---|---|---|
| **Enviar comprobante de pago** | Cliente | `Conversacion`<br>`Mensaje` | `+ agregarMensaje(...)`<br>`+ marcarEnviado(...)` | `app/Dominio/ServicioRecepcionComprobantes.php` | `ServicioRecepcionComprobantes::procesar()` | El cliente envía un comprobante en imagen o PDF a través del chat. |
| **Procesar archivo OCR** | Sistema / Empleado | `OCRService` | `+ procesarArchivo(archivo) : array` | `app/Dominio/OCRService.php` | `OCRService::procesarArchivo()` | Procesa la imagen del comprobante y extrae datos bancarios estructurados. |
| **Verificar duplicidad** | Sistema / Empleado | `Comprobante` | `+ esDuplicado() : boolean` | `app/Models/Comprobante.php`<br>`app/Dominio/ServicioComprobantes.php` | `Comprobante::esDuplicado()`<br>`ServicioComprobantes::esDuplicado()` | Comprueba si el hash SHA-256 o el número de operación ya fueron ingresados. |
| **Extraer datos de transacción (OCR)** | Sistema | `OCRService` | `+ extraerNumeroOperacion()`<br>`+ extraerMonto()`<br>`+ extraerFecha()` | `app/Dominio/OCRService.php` | `OCRService::extraerNumeroOperacion()`<br>`OCRService::extraerMonto()`<br>`OCRService::extraerFecha()` | Reconoce expresiones regulares de montos, fechas e identificador de pago. |
| **Visualizar bandeja de conciliación** | Empleado / Administrador | `Comprobante` | `Scope / Query` | `app/Http/Controllers/Web/ComprobanteController.php`<br>`app/Models/Comprobante.php` | `ComprobanteController::index()`<br>`Comprobante::scopePendientes()` | Muestra la lista de comprobantes pendientes clasificados por OCR. |
| **Validar comprobante de pago** | Empleado / Administrador | `Comprobante` | `Scope / Query` | `app/Http/Controllers/Web/ComprobanteController.php` | `ComprobanteController::show()` | Inspección visual del comprobante frente a las cuotas impagas del cliente. |
| **Aprobar comprobante** | Empleado / Administrador | `Comprobante` | `+ aprobar(usuario: Usuario) : void` | `app/Models/Comprobante.php`<br>`app/Dominio/ServicioComprobantes.php` | `Comprobante::aprobar()`<br>`ServicioComprobantes::conciliarYAprobar()` | Dictamina la validez de la transferencia y registra al operador responsable. |
| **Rechazar comprobante** | Empleado / Administrador | `Comprobante` | `+ rechazar(usuario, motivo) : void` | `app/Models/Comprobante.php`<br>`app/Dominio/ServicioComprobantes.php` | `Comprobante::rechazar()`<br>`ServicioComprobantes::rechazarComprobante()` | Rechaza el comprobante con justificación fundamentada. |
| **Imputar pago** | Empleado / Administrador | `Pago`<br>`Cuota` | `+ imputar(cuotas: List<Cuota>) : void`<br>`+ marcarComoPagada(pago) : void` | `app/Models/Pago.php`<br>`app/Models/Cuota.php` | `Pago::imputar()`<br>`Cuota::marcarComoPagada()` | Aplica el monto a las cuotas adeudadas, amortizando o saldando la deuda. |
| **Notificar pago aprobado** | Sistema | `NotificacionService` | `+ notificarPagoAprobado(...) : void` | `app/Dominio/NotificacionService.php` | `NotificacionService::notificarPagoAprobado()` | Envía mensaje automático por WhatsApp al cliente confirmando el pago. |
| **Notificar pago rechazado** | Sistema | `NotificacionService` | `+ notificarPagoRechazado(...) : void` | `app/Dominio/NotificacionService.php` | `NotificacionService::notificarPagoRechazado()` | Comunica al cliente el rechazo del comprobante y la causal correspondiente. |
| **Registrar motivo de rechazo** | Empleado / Administrador | `Comprobante` | Atributo `motivoRechazo` | `app/Models/Comprobante.php` | Parámetro `$motivo` en `Comprobante::rechazar()` | Almacena la razón formal del rechazo para auditoría y mensaje al cliente. |

---

## 4. Módulo 4 — Soporte Técnico y Gestión de Tickets

| Caso de Uso (Diagrama CU) | Actor | Clase UML | Método UML | Archivo PHP | Función en Código | Descripción breve |
|---|---|---|---|---|---|---|
| **Registrar reclamo técnico** | Cliente / Bot / Empleado | `ServicioTickets`<br>`Ticket` | `+ crearDesdeConversacion(...)`<br>`+ resolver() : void` | `app/Dominio/ServicioTickets.php`<br>`app/Models/Ticket.php` | `ServicioTickets::crearDesdeConversacion()` | Registra la manifestación de una falla técnica del cliente en el sistema. |
| **Generar ticket de soporte** | Bot de WhatsApp / Empleado | `Ticket` | `Ticket::create(...)` | `app/Models/Ticket.php`<br>`app/Dominio/ServicioTickets.php` | `Ticket::create()` | Instancia el ticket técnico con estado inicial pendiente asociado al servicio. |
| **Filtrar y listar tickets** | Empleado / Administrador | `Ticket` | `Scope / Query` | `app/Http/Controllers/Web/TicketController.php`<br>`app/Models/Ticket.php` | `TicketController::index()` | Permite buscar y filtrar tickets por estado, prioridad, técnico y fecha. |
| **Consultar tickets asignados** | Empleado | `Empleado` | `+ consultarTicketsAsignados() : List<Ticket>` | `app/Models/Empleado.php` | `Empleado::consultarTicketsAsignados()` | Devuelve los incidentes asignados al operador o técnico en turno. |
| **Asignar ticket a técnico** | Empleado / Administrador | `Ticket` | `+ asignar(usuario: Usuario) : void` | `app/Models/Ticket.php` | `Ticket::asignar($usuario)` | Asigna formalmente el responsable técnico y cambia el estado a En Proceso. |
| **Gestionar estado de ticket** | Empleado / Administrador | `Ticket` | `+ resolver() : void`<br>`+ cerrar() : void`<br>`+ reabrir() : void` | `app/Models/Ticket.php` | `Ticket::resolver()`<br>`Ticket::cerrar()`<br>`Ticket::reabrir()` | Controla la transición de estados del incidente durante su ciclo de vida. |
| **Resolver ticket** | Empleado / Administrador | `Ticket` | `+ resolver() : void` | `app/Models/Ticket.php` | `Ticket::resolver()` | Marca el incidente como solucionado registrando la fecha/hora de resolución. |
| **Cerrar ticket** | Empleado / Administrador | `Ticket` | `+ cerrar() : void` | `app/Models/Ticket.php` | `Ticket::cerrar()` | Cierre formal y definitivo del ticket una vez verificado el servicio. |
| **Reabrir ticket** | Empleado / Administrador | `Ticket` | `+ reabrir() : void` | `app/Models/Ticket.php` | `Ticket::reabrir()` | Reabre un ticket ante persistencia de falla o disconformidad del cliente. |
| **Agregar nota interna** | Empleado / Administrador | `Ticket`<br>`NotaInterna` | `+ agregarNota(txt, u) : NotaInterna`<br>`+ registrar() : void` | `app/Models/Ticket.php`<br>`app/Models/NotaInterna.php` | `Ticket::agregarNota()`<br>`NotaInterna::registrar()` | Asienta comentarios técnicos o bitácora de seguimiento no visibles al cliente. |
| **Visualizar conversación asociada** | Empleado / Administrador | `Conversacion` | `+ obtenerHistorial() : List<Mensaje>` | `app/Models/Ticket.php`<br>`app/Models/Conversacion.php` | `$ticket->conversacion->mensajes` | Permite al técnico ver el chat completo de WhatsApp donde se originó la falla. |
| **Notificar novedad por WhatsApp** | Bot de WhatsApp / Sistema | `NotificacionService` | `+ notificarNovedad(...) : void` | `app/Dominio/NotificacionService.php` | `NotificacionService::notificarTicket()` | Despacha un mensaje automático por WhatsApp al cliente informando la solución. |

---

## 5. Módulo 6 — Usuarios, Roles y Seguridad de Acceso

| Caso de Uso (Diagrama CU) | Actor | Clase UML | Método UML | Archivo PHP | Función en Código | Descripción breve |
|---|---|---|---|---|---|---|
| **Autenticar usuario** | Empleado / Administrador | `Usuario` | `+ autenticar(contrasena: String) : boolean` | `app/Models/Usuario.php`<br>`app/Http/Controllers/Web/AutenticacionController.php` | `Usuario::autenticar()`<br>`AutenticacionController::store()` | Valida credenciales cotejando contra el hash seguro almacenado. |
| **Cerrar sesión** | Empleado / Administrador | `Usuario` | `Session / Auth` | `app/Http/Controllers/Web/AutenticacionController.php` | `AutenticacionController::destroy()` | Invalida la sesión activa y revoca el acceso a las pantallas del panel. |
| **Gestionar usuarios** | Administrador | `Administrador` | `+ gestionarUsuarios() : void` | `app/Models/Administrador.php`<br>`app/Http/Controllers/Web/UsuarioController.php` | `Administrador::gestionarUsuarios()`<br>`UsuarioController::index()` | Operación administrativa para dar de alta, listar y editar operadores. |
| **Registrar usuario** | Administrador | `Usuario`<br>`Administrador` | `+ activar() : void`<br>`+ gestionarUsuarios()` | `app/Models/Usuario.php`<br>`app/Dominio/ServicioUsuarios.php` | `Usuario::activar()`<br>`ServicioUsuarios::crear()` | Crea la cuenta de acceso asignando nombre de usuario, contraseña y rol. |
| **Editar datos de usuario** | Administrador | `Usuario`<br>`Administrador` | `+ gestionarUsuarios() : void` | `app/Dominio/ServicioUsuarios.php` | `ServicioUsuarios::actualizar()` | Modifica nombre, credenciales o perfiles del personal operativo. |
| **Listar usuarios** | Administrador | `Usuario` | `Scope / Query` | `app/Http/Controllers/Web/UsuarioController.php` | `UsuarioController::index()` | Muestra la nómina de operadores con sus roles (Empleado o Administrador). |
| **Asignar rol y nivel de acceso** | Administrador | `Administrador`<br>`Empleado` | Herencia `Usuario` | `app/Dominio/ServicioUsuarios.php` | `ServicioUsuarios::crear()` | Configura privilegios de superusuario o roles operativos específicos. |
| **Desactivar / Activar usuario** | Administrador | `Usuario` | `+ desactivar() : void`<br>`+ activar() : void` | `app/Models/Usuario.php`<br>`app/Http/Controllers/Web/UsuarioController.php` | `Usuario::desactivar()`<br>`Usuario::activar()`<br>`UsuarioController::destroy()` | Habilita o revoca el acceso del operador al sistema informático. |
| **Asignar área y turno a empleado** | Administrador | `Empleado` | Atributos `area`, `turno` | `app/Models/Empleado.php`<br>`app/Dominio/ServicioUsuarios.php` | Atributos `$area`, `$turno` | Registra el área laboral (soporte, administración) y franja horaria. |
| **Cambiar contraseña** | Empleado / Administrador | `Usuario` | `+ cambiarContrasena(nueva: String) : void` | `app/Models/Usuario.php` | `Usuario::cambiarContrasena()` | Hashea y persiste una nueva contraseña de acceso para el usuario. |
