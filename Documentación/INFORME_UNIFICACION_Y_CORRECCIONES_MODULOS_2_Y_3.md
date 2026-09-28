# Informe Técnico de Unificación y Correcciones Integrales: Módulos 2 y 3
## Integración de Atención por WhatsApp (M2) y Gestión de Pagos / Conciliación (M3)

**Sistema:** Villafañe Wifi (Gestión y Atención al Cliente)  
**Cátedra:** Seminario de Integración (2026) — Licenciatura en Sistemas de Información, UCP Sede Formosa  
**Docente:** Dr. Cristian Fernando Cerquand  
**Autores del Proyecto:** Belazquez Agustín Lautaro - Serrano Ulises Iván  
**Rama Git de Unificación:** `union-m2-m3`  
**Commits Clave:**
* `c1486ef` (Módulo 2 base — Ulises)
* `549edaf` (Módulo 3 base — Agustín)
* `d77bb31` (Merge de unificación — Ulises)
* Saneamiento y correcciones finales (Agustín / Antigravity Assistant)

---

## 1. Introducción y Contexto de la Unificación

En el marco del desarrollo incremental del sistema **Villafañe Wifi**, los **Módulos 2 y 3** representan el núcleo transaccional y operativo más estrechamente interconectado del negocio:
* **Módulo 2 (Atención al Cliente por WhatsApp y Gestión de Conversaciones - RF-10 al RF-19):**  
  Constituye el canal de contacto directo con los abonados. A través de la API Cloud de WhatsApp y un bot interactivo con IA, los clientes consultan su estado de cuenta (**RF-13**), registran reclamos técnicos (**RF-14**) y **envían comprobantes de pago como imágenes o PDFs (RF-19)**.
* **Módulo 3 (Gestión de Pagos, Comprobantes y Conciliación - RF-20 al RF-26):**  
  Constituye el motor administrativo y contable. Procesa los comprobantes recibidos mediante OCR (**RF-20**), detecta duplicados por hash criptográfico SHA-256 (**RF-22**), provee la bandeja de conciliación web para los empleados (**RF-23** y **RF-24**), imputa automáticamente los pagos cancelando las cuotas más antiguas en cascada (**RF-25**) y notifica por WhatsApp al cliente el resultado de la validación (**RF-26**).

Dado que el Módulo 2 captura los comprobantes y el Módulo 3 los procesa e impacta en la cuenta corriente, ambos desarrollos debían unificarse en una única rama (`union-m2-m3`). Debido a que Agustín y Ulises desarrollaron sus respectivos módulos en ramas paralelas (`modulo-3-pagos` y `modulo-2-conversaciones`), surgieron discrepancias de nomenclatura, superposiciones de métodos y desajustes en los diagramas generales.

---

## 2. Detalle de las Soluciones y Aportes del Compañero (Ulises) en el Merge (`d77bb31`)

Al momento de realizar el merge de la rama `modulo-3-pagos` dentro de `union-m2-m3`, Ulises llevó a cabo una serie de integraciones estructurales correctas:

### 2.1. Unificación en Base de Datos (Migraciones)
* **Preservación de la migración base de comprobantes:**  
  Mantuvo la migración [`2026_09_27_120000_create_comprobante_table.php`](file:///c:/Programacion/Xamp/htdocs/Proyecto%20Seminario%20de%20Integracion%20Villfa%C3%B1e%20Wifi/VillafaneWifi-Gestion-y-atencion/Codigo/database/migrations/2026_09_27_120000_create_comprobante_table.php) desarrollada para el Módulo 3, la cual define las columnas requeridas para la conciliación bancaria y contable: `id_cliente`, `id_pago`, `hash_archivo`, `numero_operacion`, `monto_ocr`, `fecha_ocr`, `ruta_archivo`, `estado_validacion`, `motivo_rechazo` y `origen`.
* **Adición no destructiva del contexto conversacional:**  
  Agregó la migración [`2026_09_27_152000_add_whatsapp_context_to_comprobante_table.php`](file:///c:/Programacion/Xamp/htdocs/Proyecto%20Seminario%20de%20Integracion%20Villfa%C3%B1e%20Wifi/VillafaneWifi-Gestion-y-atencion/Codigo/database/migrations/2026_09_27_152000_add_whatsapp_context_to_comprobante_table.php), que añade de forma complementaria las claves y metadatos de WhatsApp: `id_conversacion`, `id_mensaje`, `fecha_recepcion`, `nombre_original`, `mime_type` y `tamanio_bytes`. De esta manera, un comprobante puede nacer tanto de una conversación de WhatsApp como de una carga manual en el panel sin provocar inconsistencias de esquema.

### 2.2. Unificación en el Modelo Eloquent (`Comprobante.php`)
* Integró en [`Codigo/app/Models/Comprobante.php`](file:///c:/Programacion/Xamp/htdocs/Proyecto%20Seminario%20de%20Integracion%20Villfa%C3%B1e%20Wifi/VillafaneWifi-Gestion-y-atencion/Codigo/app/Models/Comprobante.php) los `$fillable` y `$casts` de ambos módulos, consolidando las relaciones:
  - `cliente()`: `BelongsTo` (M3).
  - `pago()`: `BelongsTo` (M3).
  - `conversacion()`: `BelongsTo` (M2).
  - `mensaje()`: `BelongsTo` (M2).

### 2.3. Integración de Vistas Blade y Rutas Web
* **Vistas:** Combinó la carpeta `resources/views/comprobantes` (bandeja de conciliación, modal de aprobación/rechazo y formulario de subida manual aportados por Agustín) con `resources/views/conversaciones` y `simulador-whatsapp` (aportados por Ulises).
* **Menú Lateral (`layouts/panel.blade.php`):** Añadió enlaces condicionados por permisos tanto para "Conversaciones WhatsApp" como para "Bandeja de Comprobantes".
* **Rutas (`routes/web.php`):** Unificó los grupos de rutas protegidas bajo middleware `verificar.accion`, asociando los endpoints de `ComprobanteController` y `ConversacionController`.

### 2.4. Matriz de Autorización (`PoliticaEmpleado.php`)
* Integró en la política de control de acceso por área las nuevas acciones:
  - `AccionSistema::ConsultarConversaciones` y `GestionarConversaciones` (M2).
  - `AccionSistema::ConsultarComprobantes` y `GestionarComprobantes` (M3).

---

## 3. Errores Críticos e Inconsistencias Residuales del Merge

A pesar de los aciertos en la fusión global, la resolución del conflicto de merge presentó **tres problemas inadvertidos** que impedían el funcionamiento normal del sistema y la coherencia formal exigida por la cátedra:

### 3.1. Error Fatal de PHP: Método Duplicado en `Pago.php`
* **Causa:** Tanto Agustín en M3 como Ulises en M2 habían incorporado la relación inversa hacia el comprobante dentro del modelo [`Pago.php`](file:///c:/Programacion/Xamp/htdocs/Proyecto%20Seminario%20de%20Integracion%20Villfa%C3%B1e%20Wifi/VillafaneWifi-Gestion-y-atencion/Codigo/app/Models/Pago.php). Al fusionar los archivos, se conservaron ambas declaraciones:
  ```php
  // Declaración 1 (línea 35)
  public function comprobante(): BelongsTo
  {
      return $this->belongsTo(Comprobante::class, 'id_comprobante', 'id_comprobante');
  }
  
  // Declaración 2 (línea 45) - REDUNDANTE
  public function comprobante(): BelongsTo
  {
      return $this->belongsTo(Comprobante::class, 'id_comprobante', 'id_comprobante');
  }
  ```
* **Consecuencia:** La clase `Pago` generaba un error fatal insalvable:  
  `Fatal error: Cannot redeclare App\Models\Pago::comprobante() in Pago.php on line 45`.  
  Esto provocaba que **ningún pago pudiera imputarse**, la conciliación fallara y los tests de Laravel no pudieran ejecutarse.

### 3.2. Deprecación de Sintaxis en `PagoController.php`
* En la línea 24 de [`PagoController.php`](file:///c:/Programacion/Xamp/htdocs/Proyecto%20Seminario%20de%20Integracion%20Villfa%C3%B1e%20Wifi/VillafaneWifi-Gestion-y-atencion/Codigo/app/Http/Controllers/Web/PagoController.php), la cadena `"Pago #{$pago->id_pago} registrado por ${$pago->monto_total}."` utilizaba la sintaxis obsoleta `${...}` (variable variables), generando advertencias de deprecación en PHP 8.2 y superiores.

### 3.3. Casos de Uso Huérfanos en `Diagrama Caso de uso general.drawio`
* **Causa:** En la rama de M2 se había utilizado una versión limpia y moderna del diagrama general con identificadores claros (`uc-attend`, `uc-validate`, etc.), pero donde se habían desacoplado temporalmente los conectores del Módulo 3 para evitar contaminar la entrega del M2.
* **Consecuencia:** Al imponerse la versión de M2 en el merge, quedaron **4 casos de uso del Módulo 3 huérfanos (dibujados en el lienzo pero con grado 0, sin ninguna flecha conectada)**:
  1. `uc-ocr` (*Extraer datos mediante OCR*)
  2. `uc-account` (*Consultar cuenta corriente*)
  3. `uc-installments` (*Gestionar cuotas*)
  4. `uc-bank` (*Gestionar cuentas receptoras*)
  
  Esto representaba una falla grave de trazabilidad y violación de la teoría de UML para la entrega académica.

---

## 4. Detalle de las Correcciones Realizadas en Esta Sesión (Agustín / Antigravity)

Para subsanar las fallas residuales y dejar la rama `union-m2-m3` en estado de excelencia técnica, se aplicaron las siguientes soluciones:

### 4.1. Corrección en el Código Fuente

1. **Eliminación del método redundante en [`Pago.php`](file:///c:/Programacion/Xamp/htdocs/Proyecto%20Seminario%20de%20Integracion%20Villfa%C3%B1e%20Wifi/VillafaneWifi-Gestion-y-atencion/Codigo/app/Models/Pago.php):**  
   Se removió la segunda declaración de `comprobante()`, restaurando la compilación limpia del modelo y habilitando la navegación bidireccional `Pago <-> Comprobante`.
2. **Corrección de interpolación en [`PagoController.php`](file:///c:/Programacion/Xamp/htdocs/Proyecto%20Seminario%20de%20Integracion%20Villfa%C3%B1e%20Wifi/VillafaneWifi-Gestion-y-atencion/Codigo/app/Http/Controllers/Web/PagoController.php):**  
   Se escapó el signo de peso `\${$pago->monto_total}`, erradicando cualquier aviso de deprecación en el runtime de PHP.
3. **Validación de la Suite de Tests:**  
   Se ejecutó PHPUnit / Artisan Test con PHP 8.5:
   ```text
   Tests:       34 passed (100%)
   Assertions:  114 verificadas
   Duration:    1.73s
   Failures:    0
   Errors:      0
   ```
   Se validó exitosamente:
   * Alta, baja y edición de clientes y servicios (M1).
   * Webhook, recepción de mensajes, simulador y derivación humana (M2).
   * Subida manual, recepción por webhook, simulación OCR, aprobación con imputación a cuotas viejas primero, rechazo con motivo y notificaciones de WhatsApp (M3).

### 4.2. Corrección en los Diagramas Generales

En el archivo [`Diagramas/Diagrama Caso de uso general.drawio`](file:///c:/Programacion/Xamp/htdocs/Proyecto%20Seminario%20de%20Integracion%20Villfa%C3%B1e%20Wifi/VillafaneWifi-Gestion-y-atencion/Diagramas/Diagrama%20Caso%20de%20uso%20general.drawio) se incorporaron **7 conectores formales UML** con enrutamiento ortogonal y semántica estricta:

| Conector ID | Tipo de Relación | Origen (`source`) | Destino (`target`) | Justificación Teórica y de Negocio |
| :--- | :--- | :--- | :--- | :--- |
| `ei14_m3` | `«include»` | `uc-receipt` (*Recibir comprobante*) | `uc-ocr` (*Extraer datos OCR*) | Todo comprobante recibido ingresa obligatoriamente al pipeline de extracción OCR (**RF-20**). |
| `ei18_m3` | `«include»` | `uc-validate` (*Validar comprobante*) | `uc-result` (*Confirmar o rechazar*) | El acto de conciliar en el panel concluye necesariamente en la aprobación o el rechazo (**RF-24**). |
| `ei19_m3` | `«include»` | `uc-update-account` (*Actualizar cta cte*) | `uc-installments` (*Gestionar cuotas*) | Al registrar el pago se imputan y cancelan las cuotas impagas del cliente (**RF-25**). |
| `ea14_m3` | Asociación directa | `a-employee-pay` (*Empleado Cobranzas*) | `uc-bank` (*Gestionar cuentas*) | El área administrativa gestiona las cuentas bancarias y billeteras receptoras (**RF-09**). |
| `ea15_m3` | Asociación directa | `a-employee-pay` (*Empleado Cobranzas*) | `uc-installments` (*Gestionar cuotas*) | El empleado supervisa la facturación y generación de cuotas mensuales. |
| `ea16_m3` | Asociación directa | `a-employee-pay` (*Empleado Cobranzas*) | `uc-account` (*Consultar cuenta corriente*) | El empleado de administración consulta el saldo y mora del abonado (**RF-05**). |
| `ea17_m3` | Asociación directa | `a-client` (*Cliente*) | `uc-account` (*Consultar cuenta corriente*) | El cliente puede solicitar su saldo y vencimientos tanto vía web como vía bot (**RF-13**). |

> **Auditoría de Grafos:** Se ejecutó un script de verificación de grado sobre el archivo `.drawio`. El resultado arrojó **0 nodos con grado 0**. Todos los casos de uso del sistema poseen conexión directa con un actor o con un caso de uso base mediante `«include»` o `«extend»`.

---

## 5. Matriz de Concordancia Integral: Requerimientos vs. Código vs. Diagramas

La siguiente tabla resume cómo cada requerimiento de los Módulos 2 y 3 se encuentra reflejado idénticamente en el código fuente de Laravel y en los diagramas de la rama `union-m2-m3`:

| RF | Requisito del Sistema | Implementación en Código (Laravel) | Reflejo en Diagrama de Clases | Reflejo en Diagrama Caso de Uso | Reflejo en Secuencia (BCE) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **RF-10** | Recepción y bot WhatsApp | `WebhookWhatsappController`, `WhatsAppService` | Métodos en `Conversacion`, `Mensaje` | `uc-attend` $\xrightarrow{\text{inc}}$ `uc-identify` | Mensaje entrante Meta $\rightarrow$ Webhook $\rightarrow$ Dominio |
| **RF-11** | Intención por IA | `IAService::interpretarIntencion()` | `IAService` asociado a `Conversacion` | `uc-attend` $\xrightarrow{\text{inc}}$ `uc-intent` | Invocación `IAService` en marco `alt` |
| **RF-12** | Identificación de cliente | `ServicioIdentificacion::identificar()` | Asociación `Cliente 1 -- 0..* Conversacion` | `uc-identify` (`«include»`) | Búsqueda por número en `Cliente` |
| **RF-13** | Consulta de saldo | `ServicioCuentaCorriente::obtenerEstado()` | Métodos en `Cliente` y `Servicio` | `uc-status` y `uc-account` | Lectura de cuotas en `MariaDB` |
| **RF-14** | Reclamos y tickets | `ServicioTickets::crearTicket()` | `Ticket` asociado a `Conversacion` | `uc-claim` $\xrightarrow{\text{inc}}$ `uc-create-ticket` | Creación de entidad `Ticket` |
| **RF-15** | Escalado humano | `ServicioAtencionHumana::derivar()` | Atributo `modoAtencion`, método `derivar()` | `uc-escalate` (`«extend»`) | Notificación en cola de atención |
| **RF-16** | Historial de mensajes | Modelo `Mensaje`, `ConversacionController` | `Conversacion 1 -- * Mensaje` | `uc-history` | Persistencia en tabla `mensaje` |
| **RF-17** | Cierre de conversación | `Conversacion::cerrar()` | Método `cerrar()` en `Conversacion` | `uc-close` | Actualización de `fechaHoraCierre` |
| **RF-18** | Avisos de vencimiento | `ServicioNotificacionesVencimiento` | `ServicioNotificacionesVencimiento` | `uc-due-notify` | Tarea programada por cron/comando |
| **RF-19** | Recepción comprobante | `ServicioRecepcionComprobantes` | `Mensaje 1 -- 0..1 Comprobante` | `uc-receipt` | Recepción de archivo multimedia |
| **RF-20** | Procesamiento OCR | `OCRService::procesar()` | `OCRService` con `extraerMonto()`, etc. | `uc-receipt` $\xrightarrow{\text{inc}}$ `uc-ocr` | Llamada a servicio OCR |
| **RF-22** | Comprobante duplicado | `Comprobante::esDuplicado()`, `hash_archivo` | Atributo `hashArchivo` y método `esDuplicado()` | CU M3: `Verificar duplicidad SHA-256` | Comparación de hash criptográfico |
| **RF-23** | Bandeja de conciliación | `ComprobanteController::index()` | Atributo `estadoValidacion` | `uc-validate` asociado a `Empleado` | Petición GET a `/comprobantes` |
| **RF-24** | Aprobación / Rechazo | `ComprobanteController::aprobar() / rechazar()` | Métodos `aprobar()` y `rechazar()` | `uc-validate` $\xrightarrow{\text{inc}}$ `uc-result` | Petición POST con modal y motivo |
| **RF-25** | Imputación a cuotas | `ServicioFacturacion::imputarPagoACuotas()` | Método `imputar()` en clase `Pago` | `uc-result` $\xrightarrow{\text{inc}}$ `uc-update-account` | Transacción DB: Cuotas $\rightarrow$ Pagadas |
| **RF-26** | Notificación resultado | `NotificacionService::notificarPago...()` | `NotificacionService` (servicio externo) | `uc-result` $\xrightarrow{\text{inc}}$ `uc-notify-result` | Envío de WhatsApp saliente |

---

## 6. Estado de los Diagramas Específicos

En el directorio `Diagramas/` se conserva la simetría y excelencia visual para la entrega final:
1. **Módulo 1:** `Diagrama de Caso de Uso Modulo 1.drawio` y `Diagrama de Secuencia Modulo 1.drawio`.
2. **Módulo 2:** `Diagrama de Caso de Uso Modulo 2.drawio` y `Diagrama de Secuencia Modulo 2.drawio` (consolidado único que cubre RF-10 a RF-19).
3. **Módulo 3:** `Diagrama de Caso de Uso Modulo 3.drawio` y `Diagrama de Secuencia Modulo 3.drawio` (consolidado con marcos `alt` de aprobación/rechazo y flujo completo de conciliación).
4. **Módulo 6:** `Diagrama de Caso de Uso Modulo 6.drawio` y `Diagrama de Secuencia Modulo 6.drawio`.

---

## 7. Conclusión y Dictamen Final

La unificación en la rama **`union-m2-m3`** ha superado exitosamente la fase de integración y saneamiento:
* **El código fuente es robusto:** No existen métodos duplicados, no hay advertencias de sintaxis y los 34 tests automatizados de la suite pasan al 100%.
* **La base de datos es armónica:** La tabla `comprobante` satisface tanto las necesidades del bot conversacional como las del operador contable sin colisiones.
* **Los diagramas son impecables:** El Diagrama de Caso de Uso General no posee casos huérfanos, el Diagrama de Clases respeta POO puro sin contaminación de atributos relacionales tipo FK, y los diagramas de secuencia modelan la arquitectura BCE fielmente.

La rama está lista para ser presentada ante la cátedra o fusionada a la rama principal (`main`).
