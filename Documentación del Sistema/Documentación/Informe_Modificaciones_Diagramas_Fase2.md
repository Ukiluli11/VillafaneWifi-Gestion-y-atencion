# INFORME DE EVOLUCIÓN DE DISEÑO
## Refinamiento de Requerimientos Funcionales y Actualización de Modelos de Dominio
**Proyecto:** Sistema de Gestión y Atención — Villafañe Wifi  
**Módulo:** Módulo 2: Atención por WhatsApp, Reconocimiento de Intenciones y Notificaciones  
**Metodología:** Ciclo de Vida Incremental e Iterativo (Sommerville / Pressman)  
**Fecha de Revisión:** Septiembre 2026 — Hito de Entrega Avance 2  

---

## 1. Propósito y Justificación Metodológica

El presente informe documenta formalmente las razones técnicas, operativas y de ingeniería de software que motivaron la revisión y actualización de los diagramas del sistema:
- **Diagrama de Entidad-Relación Conceptual (DER Conceptual)**
- **Diagrama de Entidad-Relación Lógico (DER Lógico en 3FN)**
- **Diagrama de Clases de Diseño (UML)**

Bajo el marco del **Ciclo de Vida Incremental e Iterativo** exigido por la cátedra, cada incremento del sistema atraviesa una fase rigurosa de análisis de requerimientos en profundidad previa a su consolidación definitiva. Durante la especificación formal y el análisis detallado de los Requerimientos Funcionales del Módulo 2 (**RF-10 a RF-18**, correspondientes a la atención desatendida vía WhatsApp, recepción inteligente de comprobantes y avisos preventivos de cobro), el equipo de desarrollo detectó que el modelado preliminar concebido en la Fase 1 presentaba vacíos conceptuales y omisiones estructurales que no permitían dar soporte fiel a la operativa real de negocio.

En estricto cumplimiento de los principios de ingeniería de software y trazabilidad (**Requerimientos → Modelos de Análisis/Diseño → Código Fuente → Pruebas de Software**), se procedió a adecuar y perfeccionar los diagramas antes de consolidar la arquitectura de implementación, garantizando un modelo consistente, normalizado en Tercera Forma Normal (3FN) y sin deuda técnica.

---

## 2. Análisis de Discrepancias Detectadas en Requerimientos y Causas del Refinamiento

### 2.1. Canalización y Persistencia de Avisos Preventivos de Vencimiento (RF-18)
- **Situación Inicial en Fase 1:** Se asumía que los recordatorios de vencimiento de facturas y los avisos de corte eran meros "mensajes salientes" efímeros enviados por el canal de WhatsApp, asimilándolos a la entidad genérica `Mensaje`.
- **Hallazgo de Requerimiento:** El requerimiento **RF-18** contempla notificar por WhatsApp los servicios próximos a vencer o vencidos. Para implementar el envío con trazabilidad, se modeló el aviso como un evento de negocio con auditoría propia que requiere:
  1. *Control de estados del aviso:* `pendiente`, `enviado` o `fallido`.
  2. *Trazabilidad de reintentos y fecha/hora exacta del último intento* para evitar saturación de la API de Meta.
  3. *Registro del detalle de error* devuelto por la pasarela en caso de fallas de conectividad.
  4. *Restricción de unicidad compuesta `[id_cuota, tipo]`* para conservar un registro por cuota y clase de aviso y evitar reenvíos duplicados después de un envío exitoso. Los intentos fallidos se pueden reintentar sobre el mismo registro.
- **Solución de Diseño:** Se incorporó la entidad formal **`AvisoVencimiento`** (`aviso_vencimiento`) en el DER Conceptual, DER Lógico y Diagrama de Clases, vinculada a `Cuota` (1 a N obligatoria) y canalizada opcionalmente a través de `Conversacion` y `Mensaje`.

### 2.2. Desacoplamiento de Cliente e Identificación Dinámica en el Chatbot (RF-10 y RF-12)
- **Situación Inicial en Fase 1:** El diseño preliminar estipulaba una relación obligatoria (1 a N) entre `Cliente` y `Conversacion`, exigiendo que todo chat entrante estuviera rígidamente atado a un `id_cliente` existente desde el momento en que se abría el hilo de WhatsApp.
- **Hallazgo de Requerimiento:** En la realidad operativa del sistema, cualquier persona puede enviar un mensaje al número de la empresa. Exigir que `id_cliente` fuera obligatorio generaba dos problemas críticos de análisis:
  1. Imposibilidad de atender consultas de prospectos o nuevos usuarios que aún no son clientes (**RF-10**).
  2. Imposibilidad de atender a clientes ya registrados cuyo número de teléfono no coincidía inicialmente con el registrado en base de datos o que requerían un proceso de identificación guiada por DNI (**RF-12**).
- **Solución de Diseño:** Se flexibilizó `id_cliente` como atributo opcional (*nullable*) en `conversacion`, y se incorporó el atributo estructurado `datos_registro` (JSON) para almacenar la sesión interactiva del alta de clientes, manteniendo número de teléfono de WhatsApp como identificador de sesión y vinculando el `id_cliente` sólo cuando la identidad es confirmada.

### 2.3. Máquina de Estados del Diálogo y Control de Intentos de Comprensión (RF-11 y RF-15)
- **Situación Inicial en Fase 1:** El modelado inicial únicamente preveía estados globales de conversación (`abierta`, `cerrada`).
- **Hallazgo de Requerimiento:** El funcionamiento de un chatbot con procesamiento de lenguaje natural y menú estructurado exige almacenar en la base de datos el punto exacto de la interacción en el que se encuentra el usuario. Sin persistencia de estado de flujo, un reinicio del servicio o una nueva solicitud entrante provocaría la pérdida del contexto conversacional (por ejemplo, si el bot estaba esperando la imagen de un comprobante o la descripción de una falla técnica). Asimismo, para cumplir con la reformulación y escalado automático tras dos fallos de intención (**RF-11**), era indispensable persistir un contador específico de intentos fallidos.
- **Solución de Diseño:** Se agregaron formalmente a la entidad `conversacion` los atributos `estado_flujo` (control de pasos de árbol) e `intentos_intencion` (contador de fallos para derivación a soporte).

### 2.4. Recepción de comprobantes y brechas de OCR/validación (RF-19 a RF-26)
- **Situación Inicial en Fase 1:** El diseño preliminar asociaba de manera directa un pago a una conversación sin mecanismos de verificación de duplicados digitales ni trazabilidad analítica de lectura OCR.
- **Hallazgo de Requerimiento:** El envío del archivo corresponde a **RF-19**. El procesamiento OCR, reenvío por baja calidad, detección de duplicados, conciliación, aprobación/rechazo y notificación del resultado corresponden a **RF-20 a RF-26**. La estructura actual contempla datos para esos pasos, pero el flujo implementado solo recibe el archivo y lo deja pendiente; no debe afirmarse que OCR o la prevención de fraude ya funcionan.
  1. Cálculo y almacenamiento del **hash criptográfico SHA-256** del archivo binario con índice `UNIQUE` a nivel de base de datos.
  2. Persistencia desacoplada del resultado OCR: monto extraído, fecha leída, número de operación y porcentaje de confianza probabilística.
  3. Relación de integridad referencial optativa (0..1 a 1) entre `Comprobante` y `Pago`: no todo pago requiere un comprobante WhatsApp (pudo realizarse en efectivo en oficina), pero cada comprobante validado sólo puede acreditar a lo sumo un pago contable.

### 2.5. Notas internas de tickets: requerimiento vigente aún no implementado (RF-32)
- El requerimiento **RF-32** solicita que empleados y administradores agreguen notas internas a los tickets. Sin embargo, la versión actual del código no tiene modelo, migración ni flujo para `NotaInterna`. Por ese motivo se quitó del DER lógico como si ya estuviera persistida, pero se conserva como brecha pendiente en el modelo de alcance/diseño. El historial de WhatsApp no sustituye una nota interna asociada al ticket, porque tiene otro propósito y audiencia.

---

## 3. Inventario de Modificaciones Concretas en los Diagramas

| Diagrama | Elemento Modificado | Tipo de Modificación | Justificación / RF Asociado |
|---|---|---|---|
| **DER Conceptual** | Entidad `AVISO DE VENCIMIENTO` | Incorporación | RF-18: Se modela como entidad de negocio para registrar el aviso preventivo o de mora y su resultado de envío. |
| **DER Conceptual** | Relación `CUOTA — AVISO` | Nueva relación 1:N | Una cuota genera avisos preventivos y por mora; el aviso audita el estado del recordatorio. |
| **DER Conceptual** | Relación `CONVERSACIÓN — AVISO` | Nueva relación 0..1:N | Canalización opcional del aviso mediante el chat de WhatsApp del cliente. |
| **DER Lógico** | Tabla `aviso_vencimiento` | Nueva tabla en 3FN | Estructura completa con PK, FK a cuota, FKs opcionales a conversación/mensaje y UNIQUE compuesto. |
| **DER Lógico** | Tabla `conversacion` | Modificación de columnas | `id_cliente` como nullable; incorporación de `estado_flujo`, `intentos_intencion` y `datos_registro` (JSON). |
| **DER Lógico** | Tabla `comprobante` | Atributos preparados para auditoría/OCR | RF-19 a RF-26: el esquema contiene campos OCR, operación y hash; su presencia en BD no significa que se calcule hash, se ejecute OCR o se concilie el pago. |
| **DER Lógico** | Tabla `pago` | Integridad referencial | `id_comprobante` nullable con constraint `UNIQUE` (asociación 0..1 a 1 comprobante-pago). |
| **Diagrama de Clases** | Clase `AvisoVencimiento` | Nueva clase de entidad | Atributos y relaciones revisados contra el modelo Eloquent y la migración vigentes. |
| **Diagrama de Clases** | Clase `Conversacion` | Actualización de miembros | Atributos de flujo y métodos de escalado: `usuarioAtencion()`, `avisosVencimiento()`, `tickets()`. |
| **Diagrama de Clases** | Clases `Cuota`, `Mensaje`, `Usuario` | Relaciones Eloquent | Sincronización de métodos `hasMany` y `belongsTo` para reflejar el modelo de dominio activo. |

---

## 4. Matriz de Trazabilidad: Requerimientos → Diseño → Implementación

| Requerimiento Funcional | Reflejo en Diagramas | Implementación observada | Estado / observación |
|---|---|---|---|
| **RF-10: Recepción y respuesta automática** | Caso de Uso M2; Secuencia Recepción y Bot. | `WebhookWhatsappController`, `ServicioRecepcionWhatsapp`, `ServicioEnvioWhatsapp`; tests de webhook. | Implementado para los tipos de mensaje admitidos. |
| **RF-11: Reconocimiento de intención por IA** | Caso de Uso M2; Recepción y Bot; Atención Humana. | `ServicioReconocimientoIntencionWhatsapp`, `ClasificadorIntencionGemini`, contador en conversación; tests de clasificación y dos fallos. | Implementado con Gemini cuando hay clave configurada; clasificador local de respaldo en su ausencia. |
| **RF-12: Identificación por teléfono/DNI, alta y contratación** | Caso de Uso M2; nueva Secuencia Identificación y Alta. | `ServicioRecepcionWhatsapp`, `ServicioConversaciones`, `ServicioRegistroClienteWhatsapp`; tests de DNI existente y alta con servicio. | Implementado desde el chat, con creación de cliente/servicio al completar el flujo. |
| **RF-13: Consulta de estado de cuenta vía bot** | Caso de Uso M2; nueva Secuencia Consulta Estado de Cuenta. | `ServicioConsultaCuentaWhatsapp`, `ServicioFacturacion`, `ServicioCuentaCorriente`; test de consulta. | Implementado; informa mora y próximo vencimiento y cierra el flujo resuelto. |
| **RF-14: Registro de reclamos vía bot** | Caso de Uso M2; Secuencia Reclamo Técnico. | `ServicioRegistroReclamoWhatsapp`, `Ticket`; test de reclamo desde webhook. | Implementado desde WhatsApp. La creación manual corresponde a RF-27. |
| **RF-15: Toma de control por empleado/administrador** | Caso de Uso M2; Secuencia Atención Humana. | `ServicioAtencionHumanaWhatsapp`, `ServicioConversaciones`, `ConversacionController`; tests de toma, concurrencia, respuesta y cierre. | Implementado con autorización y bloqueo de doble toma. |
| **RF-16: Historial de mensajes** | Caso de Uso M2 y secuencias del módulo. | Modelo `Mensaje`, `ServicioConversaciones`, vista de detalle; pruebas de persistencia. | Implementado para tipos admitidos. Confirmar alcance respecto a tipos Meta que hoy no se procesan. |
| **RF-17: Cierre de conversación** | Caso de Uso M2; secuencias de consulta, reclamo y atención humana. | `ServicioConversaciones::cerrar` y coordinadores de flujo; tests de cierre. | Implementado y conserva el historial. |
| **RF-18: Notificación de vencimientos por WhatsApp** | Caso de Uso M2; Secuencia Avisos de Vencimiento. | `ServicioAvisosVencimiento`, comando `avisos:enviar-vencimientos`, programación diaria en `routes/console.php`; tests del comando/servicio. | Implementado en aplicación; el entorno debe ejecutar el scheduler de Laravel. |

El envío de comprobantes es **RF-19**, separado de RF-14. Se implementó recibir imagen/PDF y dejarlo pendiente. RF-20 (OCR), RF-21 (reenvío por falla de OCR), RF-22 (detección efectiva de duplicados), RF-24 (aprobar/rechazar), RF-25 (registrar el pago) y RF-26 (notificar resultado) siguen pendientes o incompletos; la presencia de campos en la tabla no demuestra esos comportamientos.

---

## 5. Conclusiones y Estado del Arte del Repositorio

1. **Rigor Metodológico Demostrado:** El proceso documentado ejemplifica el valor práctico del ciclo de vida incremental. La revisión de los requerimientos detallados permitió anticipar inconsistencias de negocio y resolverlas a nivel de modelado antes de consolidar el desarrollo.
2. **Integridad y Coherencia de los Artefactos:** Las fuentes de diagramas se cotejaron con requerimientos y código. Las ocho fuentes del Módulo 2 y las fuentes generales revisadas requieren todavía inspección visual y exportación de PNG antes de dar por cerrada su presentación.
3. **Preparación para el trabajo de comprobantes:** El esquema contiene campos previstos para OCR y unicidad, pero el flujo actual todavía no calcula el hash al recibir el archivo ni implementa extracción OCR y conciliación. Estos comportamientos deben diseñarse contra RF-20 a RF-26 antes de considerarlos terminados.
