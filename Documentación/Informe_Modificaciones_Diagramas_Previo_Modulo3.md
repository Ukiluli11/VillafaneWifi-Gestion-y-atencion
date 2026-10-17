# Informe de Modificaciones y Trazabilidad de Diagramas Previas al Desarrollo del Módulo 3
**Sistema:** Villafañe Wifi (Gestión y Atención al Cliente)  
**Materia:** Seminario de Integración (2026) — Licenciatura en Sistemas de Información, UCP Sede Formosa  
**Docente:** Dr. Cristian Fernando Cerquand  
**Autores:** Belazquez Agustín Lautaro - Serrano Ulises Iván  
**Rama de Trabajo Git:** `modulo-3-pagos`

---

## 1. Introducción y Propósito

El presente documento técnico formaliza el proceso de revisión metodológica y evolución de la ingeniería de software aplicada a los diagramas del sistema Villafañe Wifi, previo al inicio del desarrollo del **Módulo 3: Gestión de Pagos y Comprobantes**.

A partir del análisis del **Informe del Sistema** (especificación de 46 Requerimientos Funcionales) y del **material teórico de cátedra** (POO, UML, Arquitectura BCE y Base de Datos de la UCP), se auditó el estado del arte del proyecto para garantizar que todos los modelos conceptuales, de clases, casos de uso y secuencias reflejen con total fidelidad la lógica de negocio antes de implementar la solución en código Laravel 13 y MariaDB.

---

## 2. Marco Metodológico y Criterios Teóricos de Cátedra

Las decisiones de diseño tomadas se sustentan en tres principios fundamentales de la cátedra:

### 2.1. Paradigma de Objetos Puro vs. Modelo Relacional en Diagrama de Clases
De acuerdo con la teoría de POO, un diagrama de clases describe objetos en memoria y su colaboración, no tablas SQL.
* Se eliminaron los atributos de clave foránea enteros (`idCliente : int`, `idServicio : int`, `idPago : int`) que representaban "pensamiento relacional" redundante.
* En POO, la navegación entre entidades se expresa estructuralmente mediante **líneas de asociación** y parámetros tipados con clases de dominio (`comprobante: Comprobante`, `cuenta: CuentaReceptora`, `servicio: Servicio`).

### 2.2. Semántica Formal de Casos de Uso (UML)
* **`«include»`:** Comportamiento siempre obligatorio que forma parte indisoluble del caso de uso base.
* **`«extend»`:** Comportamiento condicional u opcional, con **puntos de extensión explícitos entre corchetes** (ej. `[OCR no concluyente]`, `[comprobante legítimo]`, `[hash duplicado]`).
* **Actor Externo Secundario (`Billetera Virtual` / Mercado Pago):** Justificado teóricamente como sistema externo certificador de la transacción que emite el comprobante y provee los datos de la transferencia.

### 2.3. Patrón Arquitectónico BCE en Diagramas de Secuencia
Se estructuraron las líneas de vida respetando estrictamente las capas:
* **Boundary (Límite):** Interfaz web del usuario (`Panel Web - Bandeja Conciliación`) y puntos de entrada de mensajería (`WhatsApp Webhook`).
* **Controller (Controlador):** Controladores HTTP de Laravel (`ControladorComprobantes`).
* **Control / Dominio (Servicios):** Servicios de lógica de negocio pura (`ServicioComprobantes`, `ServicioFacturacion`, `NotificacionService`).
* **Entity (Persistencia):** Base de Datos MariaDB y modelos Eloquent.

---

## 3. Modificaciones Realizadas en Diagramas Generales

### 3.1. Diagrama de Clases (`Diagramas/Diagrama de clases.drawio`)
1. **Descontaminación relacional en 8 clases:**
   * `Servicio`: Eliminados `idServicio`, `idPlan`, `idCliente`.
   * `Cuota`: Eliminados `idCuota`, `idServicio`, `idPago`; agregado atributo de dominio `estado : String`.
   * `Conversacion`: Eliminados `idConversacion`, `idCliente`, `idEmpleado`.
   * `Mensaje`: Eliminados `idMensaje`, `idConversacion`, `idUsuario`.
   * `Comprobante`: Eliminados `idComprobante`, `idMensaje`, `idUsuario`.
   * `Pago`: Eliminados `idPago`, `idComprobante`, `idCuenta`.
   * `CuentaReceptora`: Eliminado `idCuenta`.
   * `Ticket`: Eliminados `idTicket`, `idConversacion`, `idServicio`, `idUsuario`.
2. **Actualización de firmas de constructores a Objetos POO:**
   * `Pago`: `Pago(comprobante: Comprobante, cuenta: CuentaReceptora, fecha: Date, montoTotal: BigDecimal, medioPago: String)`.
   * `Comprobante`: `Comprobante(mensaje: Mensaje, hashArchivo: String, fechaRecepcion: DateTime)`.
   * `Cuota`: `Cuota(servicio: Servicio, periodo: String, monto: BigDecimal, fechaEmision: Date, fechaVencimiento: Date)`.
   * `Ticket`: `Ticket(conversacion: Conversacion, servicio: Servicio, tipo: String, descripcion: String)`.
3. **Incorporación de métodos requeridos para el Módulo 3:**
   * `Pago`: `+ imputarCuotas(cuotas: List<Cuota>) : void` (desencadena la liquidación en cascada de cuotas impagas, RF-25).
   * `OCRService`: `+ extraerNumeroOperacion(imagen: String) : String` (permite la lectura del código de operación bancaria para conciliación, RF-20).

### 3.2. Diagrama de Caso de Uso General (`Diagramas/Diagrama Caso de uso general.drawio`)
* **Ratificación del Actor Externo `Billetera Virtual`:** Se mantuvo conectado a la confirmación/soporte del pago como sistema secundario certificado.
* **Alineación con la Bandeja de Conciliación:** Se vinculó el flujo de validación a los actores internos `Empleado` y `Dueño / Administrador`.
* **Coherencia de Include/Extend:** Confirmar pago incluye la actualización de cuenta corriente y la notificación; la solicitud de reenvío extiende ante fallas de lectura.

### 3.3. DER Conceptual y DER Lógico
* Se ratificó que las 14 entidades/tablas del modelo de datos (`COMPROBANTE`, `PAGO`, `CUENTA_RECEPTORA`, `CUOTA`) contienen todos los campos necesarios para Módulo 3 (`hash_archivo` UNIQUE SHA-256, `monto_ocr`, `fecha_ocr`, `numero_operacion`, `estado_validacion`, `motivo_rechazo`, `fecha_hora_validacion`), con cardinalidades `(1,1)` a `(0,1)` y `(1,N)` matemáticamente consistentes.

### 3.4. Diagrama de Despliegue
* Se verificó la presencia del nodo `<<cloudService>> Servicio OCR (Comprobantes)`, el almacenamiento de objetos S3 y la infraestructura de workers para procesamiento asíncrono.

---

## 4. Diagramas Específicos Creados para el Módulo 3

### 4.1. Diagrama de Casos de Uso del Módulo 3 (`Diagramas/Diagrama de Caso de Uso Modulo 3.drawio`)
* **Actores:** `Cliente` (vía WhatsApp Bot), `Billetera Virtual (Mercado Pago)` (Actor externo), `Empleado` y `Dueño / Administrador` (Panel Web).
* **Flujo de Entrada y Anti-fraude:**
  * `Enviar comprobante de pago`
  * `«include»` $\rightarrow$ `Procesar comprobante mediante OCR`
  * `«include»` $\rightarrow$ `Verificar duplicidad de comprobante (Hash SHA-256)`
  * `«extend» [OCR no concluyente / baja confianza]` $\leftarrow$ `Solicitar reenvío de comprobante`
  * `«extend» [hash SHA-256 ya registrado]` $\leftarrow$ `Alertar comprobante duplicado`
* **Flujo de Conciliación Web:**
  * `Visualizar bandeja de conciliación de comprobantes`
  * `Validar comprobante de pago` (`«include»` Visualizar bandeja)
  * `«extend» [comprobante legítimo y verificado]` $\leftarrow$ `Aprobar comprobante y registrar pago`
  * `«extend» [comprobante inválido, apócrifo o inconsistente]` $\leftarrow$ `Rechazar comprobante de pago`
* **Flujo de Imputación y Notificación:**
  * `Aprobar comprobante` `«include»` $\rightarrow$ `Imputar pago a cuotas pendientes` $\rightarrow$ `«include»` `Actualizar saldo en cuenta corriente`.
  * `Aprobar comprobante` `«include»` $\rightarrow$ `Notificar acreditación de pago por WhatsApp`.
  * `Rechazar comprobante` `«include»` $\rightarrow$ `Registrar motivo de rechazo` y `Notificar rechazo con motivo por WhatsApp`.
* **Soporte Administrativo:**
  * `Consultar historial de pagos y comprobantes` (Empleado / Admin).
  * `Gestionar cuentas receptoras` (Admin).

### 4.2. Diagrama de Secuencia del Módulo 3 (`Diagramas/Diagrama de Secuencia Modulo 3.drawio`)
Modelado bajo el patrón BCE con fragmento estructurado `alt`:
1. **Fase de Consulta:** `Empleado` solicita `GET /comprobantes/conciliacion` $\rightarrow$ `ControladorComprobantes` invoca `ServicioComprobantes.obtenerComprobantesPendientes()` $\rightarrow$ Consulta `SELECT * FROM comprobante WHERE estado = 'pendiente'` a `Base de Datos` $\rightarrow$ Renderizado en pantalla dividida (imagen vs datos leídos por OCR).
2. **Fase de Decisión (`alt`):**
   * **Rama `[accion == 'APROBAR']`:**
     * `UPDATE comprobante SET estado='aprobado', fecha_validacion=NOW()`.
     * `ServicioFacturacion.registrarPagoEImputar()` $\rightarrow$ `INSERT INTO pago`.
     * Consulta cuotas pendientes del cliente ordenadas cronológicamente (`ORDER BY fecha_vencimiento ASC`).
     * `imputarCuotas()`: `UPDATE cuota SET estado='pagada', id_pago=...`.
     * `actualizarSaldo()`: `UPDATE cliente SET saldo = saldo - montoTotal`.
     * `NotificacionService.notificarPagoConfirmado()` $\rightarrow$ Despacho automático de mensaje WhatsApp al cliente.
     * Retorno HTTP 200 y mensaje visual de éxito en panel web.
   * **Rama `[accion == 'RECHAZAR']`:**
     * `UPDATE comprobante SET estado='rechazado', motivo_rechazo=motivo`.
     * `NotificacionService.notificarPagoRechazado()` $\rightarrow$ Despacho de WhatsApp con motivo al cliente.
     * Retorno HTTP 200 y alerta visual en panel web.

---

## 5. Matriz de Trazabilidad Completa (Requerimiento vs. Diseño)

| Requerimiento Funcional | Caso de Uso Módulo 3 | Diagrama de Clases | Diagrama de Secuencia (BCE) |
| :--- | :--- | :--- | :--- |
| **RF-19: Envío de comprobante** | `Enviar comprobante de pago` (Cliente) | `Mensaje 1--1 Comprobante` / `WhatsAppService` | Recepción Webhook / Boundary WhatsApp |
| **RF-20: Extracción OCR** | `Procesar comprobante mediante OCR` (`«include»`) | `OCRService (extraerMonto, extraerFecha, extraerNumeroOperacion)` | Llamada a servicio de extracción y persistencia en entidad |
| **RF-21: Reintento ante fallo** | `Solicitar reenvío de comprobante` (`«extend» [OCR no concluyente]`) | `Comprobante.confianzaOcr : double` | Respuesta bot solicitando imagen clara |
| **RF-22: Detección duplicados** | `Verificar duplicidad` y `Alertar duplicado` (`«extend» [hash registrado]`) | `Comprobante.esDuplicado()` / `calcularHash()` SHA-256 | Verificación de hash único en BD y alerta |
| **RF-23: Bandeja conciliación** | `Visualizar bandeja de conciliación` (Empleado / Admin) | `Comprobante (montoOcr, fechaOcr, numeroOperacion, estado)` | `GET /comprobantes/conciliacion` en Panel Web |
| **RF-24: Confirmación / Rechazo** | `Aprobar comprobante` / `Rechazar comprobante` (`«extend»`) | `Comprobante.aprobar()` / `Comprobante.rechazar(motivo)` | `POST /comprobantes/{id}/validar` / Bifurcación en `alt` |
| **RF-25: Imputación automática** | `Imputar pago a cuotas pendientes` / `Actualizar saldo` (`«include»`) | `Pago.imputarCuotas()` / `Cuota.marcarComoPagada()` | `UPDATE cuota SET estado='pagada'` / `UPDATE cliente SET saldo` |
| **RF-26: Notificación WhatsApp** | `Notificar acreditación` / `Notificar rechazo` (`«include»`) | `NotificacionService.notificarPagoConfirmado()` / `notificarPagoRechazado()` | Llamada asíncrona a WhatsApp API post-validación |
| **RF-09: Cuentas receptoras** | `Gestionar cuentas receptoras` (Administrador) | `CuentaReceptora (activar, desactivar, listarActivas)` | Asociación de pago a cuenta receptora en BD |

---

## 6. Conclusión y Visto Bueno Técnico

1. Los diagramas generales y específicos se encuentran **100% consistentes entre sí y con el Informe del Sistema**.
2. Se cumplió estrictamente con la **pureza del paradigma de objetos**, la **semántica formal de casos de uso** y el **patrón BCE en secuencias**.
3. La rama de trabajo **`modulo-3-pagos`** se encuentra al día, limpia y sincronizada con el repositorio remoto.
4. El equipo cuenta con la especificación visual y arquitectónica completa para proceder a la codificación en Laravel 13.
