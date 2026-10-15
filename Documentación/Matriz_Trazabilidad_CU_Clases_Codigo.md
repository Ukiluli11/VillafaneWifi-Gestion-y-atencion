# Matriz de Trazabilidad: Casos de Uso ↔ Diagrama de Clases ↔ Código Fuente

> **Proyecto:** Sistema de Gestión y Atención — Villafañe Wifi  
> **Propósito:** Guía de referencia rápida ante consultas de cátedra/profesor para ubicar instantáneamente la correlación exacta entre:
> 1. El **Diagrama de Casos de Uso** (óvalo)
> 2. El **Diagrama de Clases** (clase y método)
> 3. El **Código Fuente PHP** (archivo y función)

---

## 1. Módulo 1 — Gestión de Clientes, Servicios y Planes

| Caso de Uso (Diagrama CU) | Actor | Clase UML | Método UML | Archivo PHP | Función en Código |
|---|---|---|---|---|---|
| **Registrar cliente y servicio** | Empleado / Administrador | `Cliente` | `+ darDeAlta() : void` | `app/Models/Cliente.php` | `darDeAlta()` |
| **Suspender cliente** | Empleado / Administrador | `Cliente` | `+ suspender() : void` | `app/Models/Cliente.php` | `suspender()` |
| **Dar de baja definitiva del servicio** | Empleado / Administrador | `Cliente` | `+ darDeBaja() : void` | `app/Models/Cliente.php` | `darDeBaja()` |
| **Suspender servicio por falta de pago** | Reloj del Sistema / Admin | `Servicio` | `+ suspender() : void` | `app/Models/Servicio.php` | `suspender()` |
| **Reactivar servicio tras acreditación** | Empleado / Admin | `Servicio` | `+ reactivar() : void` | `app/Models/Servicio.php` | `reactivar()` |
| **Editar plan de servicio (cambio precio)** | Administrador | `Plan` | `+ cambiarPrecio(nuevo: BigDecimal) : void` | `app/Models/Plan.php` | `cambiarPrecio($nuevoPrecio)` |
| **Gestionar catálogo de planes - Activar** | Administrador | `Plan` | `+ activar() : void` | `app/Models/Plan.php` | `activar()` |
| **Dar de baja plan de servicio** | Administrador | `Plan` | `+ desactivar() : void` | `app/Models/Plan.php` | `desactivar()` |
| **Generar cuotas y actualizar vencimientos**| Reloj del Sistema | `Cuota` | `+ marcarComoVencida() : void` | `app/Models/Cuota.php` | `marcarComoVencida()` |
| **Gestionar cuentas receptoras - Activar** | Administrador | `CuentaReceptora` | `+ activar() : void` | `app/Models/CuentaReceptora.php` | `activar()` |
| **Desactivar cuenta receptora** | Administrador | `CuentaReceptora` | `+ desactivar() : void` | `app/Models/CuentaReceptora.php` | `desactivar()` |

---

## 2. Módulo 2 — Atención y Asistencia por WhatsApp

| Caso de Uso (Diagrama CU) | Actor | Clase UML | Método UML | Archivo PHP | Función en Código |
|---|---|---|---|---|---|
| **Abrir o reutilizar conversación** | Cliente / WhatsApp API | `Conversacion` | `+ iniciar() : void` | `app/Models/Conversacion.php` | `iniciar()` |
| **Cerrar conversación resuelta por bot** | WhatsApp API / Sistema | `Conversacion` | `+ cerrar() : void` | `app/Models/Conversacion.php` | `cerrar()` |
| **Cerrar atención escalada** | Empleado | `Conversacion` | `+ cerrar() : void` | `app/Models/Conversacion.php` | `cerrar()` |
| **Derivar tras segundo fallo de IA** | WhatsApp API / Sistema | `Conversacion` | `+ escalar() : void` | `app/Models/Conversacion.php` | `escalar()` |
| **Tomar control de conversación escalada**| Empleado | `Empleado` / `Conversacion` | `+ atenderConversacion(c) : void`<br>`+ derivarAUsuario(u) : void` | `app/Models/Empleado.php`<br>`app/Models/Conversacion.php` | `atenderConversacion($c)`<br>`derivarAUsuario($usuario)` |
| **Registrar mensaje en el historial** | Cliente / Sistema | `Conversacion` | `+ agregarMensaje(m) : void` | `app/Models/Conversacion.php` | `agregarMensaje($mensaje)` |
| **Consultar historial de conversación** | Empleado / Administrador | `Conversacion` | `+ obtenerHistorial() : List<Mensaje>` | `app/Models/Conversacion.php` | `obtenerHistorial()` |
| **Solicitar reformulación del mensaje** | WhatsApp API / Bot | `Conversacion` | `+ registrarIntentoFallido() : void` | `app/Models/Conversacion.php` | `registrarIntentoFallido()` |
| **Procesar mensaje entrante** | WhatsApp API | `Mensaje` / `WhatsAppService` | `+ marcarEnviado(id) : void`<br>`+ extraerMensajes(payload) : List` | `app/Models/Mensaje.php`<br>`app/Dominio/WhatsAppService.php` | `marcarEnviado($id)`<br>`extraerMensajes($payload)` |
| **Reconocer intención mediante IA** | WhatsApp API / Bot | `IAService` | `+ interpretarIntencion(texto, ctx) : array` | `app/Dominio/IAService.php` | `interpretarIntencion($texto)` |
| **Generar ticket en el sistema** | Cliente / WhatsApp API | `Ticket` | `+ asignar(usuario) : void` | `app/Models/Ticket.php` | `asignar($usuario)` |
| **Registrar reclamo o soporte técnico** | Empleado | `Ticket` | `+ resolver() : void`<br>`+ cerrar() : void` | `app/Models/Ticket.php` | `resolver()`<br>`cerrar()` |
| **Consultar tickets asignados** | Empleado | `Empleado` | `+ consultarTicketsAsignados() : List<Ticket>` | `app/Models/Empleado.php` | `consultarTicketsAsignados()` |
| **Agregar nota interna a ticket** | Empleado / Administrador | `Ticket` / `NotaInterna` | `+ agregarNota(txt, u) : NotaInterna`<br>`+ registrar() : void` | `app/Models/Ticket.php`<br>`app/Models/NotaInterna.php` | `agregarNota($txt, $u)`<br>`registrar()` |
| **Notificar vencimientos por WhatsApp** | Reloj del Sistema (Cron) | `ServicioNotificacionesVencimiento` | `+ enviar() : array` | `app/Dominio/ServicioNotificacionesVencimiento.php` | `enviar()` |

---

## 3. Módulo 3 — Conciliación y Gestión de Pagos

| Caso de Uso (Diagrama CU) | Actor | Clase UML | Método UML | Archivo PHP | Función en Código |
|---|---|---|---|---|---|
| **Aprobar comprobante y registrar pago** | Empleado / Administrador | `Comprobante` | `+ aprobar(usuario: Usuario) : void` | `app/Models/Comprobante.php` | `aprobar(Usuario $usuario)` |
| **Rechazar comprobante de pago** | Empleado / Administrador | `Comprobante` | `+ rechazar(u, motivo) : void` | `app/Models/Comprobante.php` | `rechazar(Usuario $u, $motivo)` |
| **Verificar duplicidad de comprobante** | Sistema / Empleado | `Comprobante` | `+ esDuplicado() : boolean` | `app/Models/Comprobante.php` | `esDuplicado()` |
| **Procesar comprobante mediante OCR** | Sistema / Empleado | `OCRService` | `+ procesarArchivo(archivo) : array`<br>`+ calcularConfianza(datos) : double` | `app/Dominio/OCRService.php` | `procesarArchivo($archivo)`<br>`calcularConfianza($datos)` |
| **Extraer datos clave con OCR** | Sistema | `OCRService` | `+ extraerNumeroOperacion()`<br>`+ extraerMonto()`<br>`+ extraerFecha()` | `app/Dominio/OCRService.php` | `extraerNumeroOperacion()`<br>`extraerMonto()`<br>`extraerFecha()` |
| **Imputar pago a cuotas pendientes** | Empleado / Administrador | `Pago` / `Cuota` | `+ imputar(cuotas) : void`<br>`+ marcarComoPagada(pago) : void` | `app/Models/Pago.php`<br>`app/Models/Cuota.php` | `imputar(Collection $cuotas)`<br>`marcarComoPagada(Pago $p)` |
| **Notificar pago aprobado por WhatsApp** | Sistema | `NotificacionService` | `+ notificarPagoAprobado(cliente, pago, comp)` | `app/Dominio/NotificacionService.php` | `notificarPagoAprobado()` |
| **Notificar comprobante rechazado** | Sistema | `NotificacionService` | `+ notificarPagoRechazado(cliente, comp, mot)` | `app/Dominio/NotificacionService.php` | `notificarPagoRechazado()` |

---

## 4. Módulo 6 — Autenticación y Control de Acceso

| Caso de Uso (Diagrama CU) | Actor | Clase UML | Método UML | Archivo PHP | Función en Código |
|---|---|---|---|---|---|
| **Iniciar sesión** | Empleado / Administrador | `Usuario` | `+ autenticar(contrasena) : boolean` | `app/Models/Usuario.php` | `autenticar($contrasena)` |
| **Cambiar contraseña** | Empleado / Administrador | `Usuario` | `+ cambiarContrasena(nueva) : void` | `app/Models/Usuario.php` | `cambiarContrasena($nueva)` |
| **Asignar acceso / Activar usuario** | Administrador | `Usuario` | `+ activar() : void` | `app/Models/Usuario.php` | `activar()` |
| **Desactivar usuario** | Administrador | `Usuario` | `+ desactivar() : void` | `app/Models/Usuario.php` | `desactivar()` |
| **Gestionar usuarios del sistema** | Administrador | `Administrador` | `+ gestionarUsuarios() : boolean` | `app/Models/Administrador.php` | `gestionarUsuarios()` |
| **Configurar catálogo de planes** | Administrador | `Administrador` | `+ configurarPlanes() : boolean` | `app/Models/Administrador.php` | `configurarPlanes()` |

---

## 5. Cómo responderle al profesor en 3 pasos

Si el profesor señala cualquier **Caso de Uso**:

1. **En el Diagrama de Casos de Uso:**
   * Señalar el óvalo correspondiente (ejemplo: *"Aprobar comprobante"* en el Diagrama del Módulo 3).
2. **En el Diagrama de Clases:**
   * Señalar la entidad responsable (ejemplo: *"En la clase `Comprobante`, es el método público `aprobar(usuario: Usuario)`"*).
3. **En el Código Fuente PHP:**
   * Abrir el archivo del modelo (ejemplo: `Codigo/app/Models/Comprobante.php`).
   * Mostrar la función física con su bloque de comentario PHPDoc que dice:
     ```php
     /**
      * Caso de Uso: Aprobar comprobante y registrar pago (Módulo 3)
      * Clase UML: Comprobante
      * Método UML: + aprobar(usuario: Usuario) : void
      */
     public function aprobar(Usuario $usuario): void
     {
         $this->update([
             'estado_validacion'     => EstadoComprobante::Aprobado,
             'id_usuario'            => $usuario->id_usuario,
             'fecha_hora_validacion' => Carbon::now(),
         ]);
     }
     ```
