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

### 2.1. Canalización y Persistencia de Avisos Preventivos de Vencimiento (RF-17 y RF-18)
- **Situación Inicial en Fase 1:** Se asumía que los recordatorios de vencimiento de facturas y los avisos de corte eran meros "mensajes salientes" efímeros enviados por el canal de WhatsApp, asimilándolos a la entidad genérica `Mensaje`.
- **Hallazgo de Requerimiento:** Al especificar en detalle los requerimientos **RF-17** (envío automatizado de aviso previo al vencimiento) y **RF-18** (notificación de deuda vencida), se evidenció que un aviso no es un simple texto, sino un evento de negocio con auditoría propia que requiere:
  1. *Control de estados del aviso:* `pendiente`, `enviado` o `fallido`.
  2. *Trazabilidad de reintentos y fecha/hora exacta del último intento* para evitar saturación de la API de Meta.
  3. *Registro del detalle de error* devuelto por la pasarela en caso de fallas de conectividad.
  4. *Restricción estricta de unicidad compuesta `[id_cuota, tipo]`* para garantizar que una cuota no reciba notificaciones duplicadas en el mismo período, previniendo spam involuntario al cliente.
- **Solución de Diseño:** Se incorporó la entidad formal **`AvisoVencimiento`** (`aviso_vencimiento`) en el DER Conceptual, DER Lógico y Diagrama de Clases, vinculada a `Cuota` (1 a N obligatoria) y canalizada opcionalmente a través de `Conversacion` y `Mensaje`.

### 2.2. Desacoplamiento de Cliente e Identificación Dinámica en el Chatbot (RF-10, RF-11, RF-12)
- **Situación Inicial en Fase 1:** El diseño preliminar estipulaba una relación obligatoria (1 a N) entre `Cliente` y `Conversacion`, exigiendo que todo chat entrante estuviera rígidamente atado a un `id_cliente` existente desde el momento en que se abría el hilo de WhatsApp.
- **Hallazgo de Requerimiento:** En la realidad operativa del sistema, cualquier persona puede enviar un mensaje al número de la empresa. Exigir que `id_cliente` fuera obligatorio generaba dos problemas críticos de análisis:
  1. Imposibilidad de atender consultas de prospectos o nuevos usuarios que aún no son clientes (**RF-10**).
  2. Imposibilidad de atender a clientes ya registrados cuyo número de teléfono no coincidía inicialmente con el registrado en base de datos o que requerían un proceso de identificación guiada por DNI (**RF-11**).
- **Solución de Diseño:** Se flexibilizó `id_cliente` como atributo opcional (*nullable*) en `conversacion`, y se incorporó el atributo estructurado `datos_registro` (JSON) para almacenar la sesión interactiva del alta de clientes, manteniendo número de teléfono de WhatsApp como identificador de sesión y vinculando el `id_cliente` sólo cuando la identidad es confirmada.

### 2.3. Máquina de Estados del Diálogo y Control de Intentos de Comprensión (RF-13, RF-15)
- **Situación Inicial en Fase 1:** El modelado inicial únicamente preveía estados globales de conversación (`abierta`, `cerrada`).
- **Hallazgo de Requerimiento:** El funcionamiento de un chatbot con procesamiento de lenguaje natural y menú estructurado exige almacenar en la base de datos el punto exacto de la interacción en el que se encuentra el usuario. Sin persistencia de estado de flujo, un reinicio del servicio o una nueva solicitud entrante provocaría la pérdida del contexto conversacional (por ejemplo, si el bot estaba esperando la imagen de un comprobante o la descripción de una falla técnica). Asimismo, para cumplir con el escalado automático a personal humano tras dos fallos de intención (**RF-15**), era indispensable persistir un contador específico de intentos fallidos.
- **Solución de Diseño:** Se agregaron formalmente a la entidad `conversacion` los atributos `estado_flujo` (control de pasos de árbol) e `intentos_intencion` (contador de fallos para derivación a soporte).

### 2.4. Seguridad, Integridad y Prevención de Fraudes en Comprobantes (RF-14)
- **Situación Inicial en Fase 1:** El diseño preliminar asociaba de manera directa un pago a una conversación sin mecanismos de verificación de duplicados digitales ni trazabilidad analítica de lectura OCR.
- **Hallazgo de Requerimiento:** Para mitigar el riesgo de presentación repetida de un mismo ticket de transferencia bancaria o comprobante de Mercado Pago por parte de distintos usuarios o en fechas diferidas, el análisis de seguridad exigió:
  1. Cálculo y almacenamiento del **hash criptográfico SHA-256** del archivo binario con índice `UNIQUE` a nivel de base de datos.
  2. Persistencia desacoplada del resultado OCR: monto extraído, fecha leída, número de operación y porcentaje de confianza probabilística.
  3. Relación de integridad referencial optativa (0..1 a 1) entre `Comprobante` y `Pago`: no todo pago requiere un comprobante WhatsApp (pudo realizarse en efectivo en oficina), pero cada comprobante validado sólo puede acreditar a lo sumo un pago contable.

### 2.5. Simplificación Estructural de Notas Internas en Tickets de Soporte
- Durante la revisión del módulo de tickets y reclamos, se evaluó la entidad preliminar `NotaInterna`. Se concluyó que, dado el volumen operativo y la naturaleza del flujo de soporte técnico donde el operador interactúa directamente en el hilo de la conversación escalada, la persistencia de notas en una tabla aislada agregaba sobrecarga relacional innecesaria. Se consolidaron las observaciones técnicas directamente en el ciclo de vida del `Ticket` y en los mensajes auditados del hilo conversacional.

---

## 3. Inventario de Modificaciones Concretas en los Diagramas

| Diagrama | Elemento Modificado | Tipo de Modificación | Justificación / RF Asociado |
|---|---|---|---|
| **DER Conceptual** | Entidad `AVISO DE VENCIMIENTO` | Incorporación | RF-17 y RF-18: Se modela como entidad de negocio con atributos de estado, tipo y fechas de intento. |
| **DER Conceptual** | Relación `CUOTA — AVISO` | Nueva relación 1:N | Una cuota genera avisos preventivos y por mora; el aviso audita el estado del recordatorio. |
| **DER Conceptual** | Relación `CONVERSACIÓN — AVISO` | Nueva relación 0..1:N | Canalización opcional del aviso mediante el chat de WhatsApp del cliente. |
| **DER Lógico** | Tabla `aviso_vencimiento` | Nueva tabla en 3FN | Estructura completa con PK, FK a cuota, FKs opcionales a conversación/mensaje y UNIQUE compuesto. |
| **DER Lógico** | Tabla `conversacion` | Modificación de columnas | `id_cliente` como nullable; incorporación de `estado_flujo`, `intentos_intencion` y `datos_registro` (JSON). |
| **DER Lógico** | Tabla `comprobante` | Atributos de auditoría | Incorporación de `hash_archivo` (UNIQUE), `confianza_ocr`, `numero_operacion` y `estado_validacion`. |
| **DER Lógico** | Tabla `pago` | Integridad referencial | `id_comprobante` nullable con constraint `UNIQUE` (asociación 0..1 a 1 comprobante-pago). |
| **Diagrama de Clases** | Clase `AvisoVencimiento` | Nueva clase de entidad | Atributos completos y métodos de negocio: `cuota()`, `conversacion()`, `mensaje()`, `marcarEnviado()`. |
| **Diagrama de Clases** | Clase `Conversacion` | Actualización de miembros | Atributos de flujo y métodos de escalado: `usuarioAtencion()`, `avisosVencimiento()`, `tickets()`. |
| **Diagrama de Clases** | Clases `Cuota`, `Mensaje`, `Usuario` | Relaciones Eloquent | Sincronización de métodos `hasMany` y `belongsTo` para reflejar el modelo de dominio activo. |

---

## 4. Matriz de Trazabilidad: Requerimientos → Diseño → Implementación

| Requerimiento Funcional | Necesidad Detectada en Relevamiento | Reflejo en Diagramas | Componente de Software / Código |
|---|---|---|---|
| **RF-10: Iniciar conversación WhatsApp** | Recibir mensajes de números no registrados previamente. | `conversacion`: `id_cliente` nullable, `numero_whatsapp` obligatorio. | `ServicioRecepcionWhatsapp` / Modelo `Conversacion` |
| **RF-11: Identificación y menú interactivo** | Guiar al cliente según su DNI o registrar datos parciales. | `conversacion`: `estado_flujo`, `datos_registro` (JSON). | `ServicioMenuWhatsapp` / `ServicioRegistroClienteWhatsapp` |
| **RF-12: Consulta de estado de cuenta** | Recuperar deudas vencidas y datos bancarios desde WhatsApp. | Relaciones `Cuota-Servicio-Cliente` vinculadas al chat. | `ServicioConsultaCuentaWhatsapp` / Modelo `Cuota` |
| **RF-13: Reconocimiento de intenciones IA** | Detectar motivo del mensaje y tolerar fallas de NLP. | `conversacion.intentos_intencion`, clase `IAService`. | `ServicioReconocimientoIntencionWhatsapp` |
| **RF-14: Recepción de comprobantes OCR** | Validar montos, evitar pagos duplicados y registrar hash. | `comprobante`: `hash_archivo` UNIQUE, `monto_ocr`, `confianza_ocr`. | `ServicioRegistroComprobanteWhatsapp` / `Comprobante` |
| **RF-15: Derivación y atención humana** | Escalar chats no resueltos o con fallos reiterados. | `conversacion`: `id_usuario_atencion`, `modo_atencion`. | `ServicioAtencionHumanaWhatsapp` |
| **RF-16: Registro de reclamos técnicos** | Vincular el reclamo originado en WhatsApp a un servicio. | `ticket`: `id_conversacion`, `id_servicio`, `id_empleado`. | `ServicioRegistroReclamoWhatsapp` / `Ticket` |
| **RF-17 y RF-18: Avisos de vencimiento** | Enviar notificaciones preventivas y de mora auditables. | Nueva entidad `AvisoVencimiento` (1:N con cuota). | `ServicioAvisosVencimiento` / `AvisoVencimiento` |

---

## 5. Conclusiones y Estado del Arte del Repositorio

1. **Rigor Metodológico Demostrado:** El proceso documentado ejemplifica el valor práctico del ciclo de vida incremental. La revisión de los requerimientos detallados permitió anticipar inconsistencias de negocio y resolverlas a nivel de modelado antes de consolidar el desarrollo.
2. **Integridad y Coherencia de los Artefactos:** Todos los diagramas del sistema (`DER_conceptual.drawio`, `DER_logico.drawio` y `Diagrama de clases.drawio`) reflejan con exactitud el dominio del problema y se encuentran en total sincronía con las migraciones MariaDB y modelos Eloquent desarrollados.
3. **Plataforma Sólida para el Módulo 3:** Al dejar formalmente normalizadas y documentadas las entidades `Comprobante`, `Pago` y `CuentaReceptora` con sus restricciones de hash, unicidad y confianza OCR, el sistema se encuentra técnicamente preparado para la implementación exhaustiva del pipeline OCR en el Módulo 3.
