# Informe Técnico de Auditoría, Modificaciones y Evolución de Diagramas
## Fase Previa a la Implementación del Módulo 2: Atención al Cliente por WhatsApp y Gestión de Conversaciones

**Proyecto:** Sistema de Gestión y Atención al Cliente - Villafañe Wifi  
**Cátedra:** Seminario de Integración  
**Rama de Trabajo:** `Modulo2Actualizacion`  
**Fecha:** 27 de Septiembre de 2026  

---

## 1. Resumen Ejecutivo y Propósito

El presente documento tiene como finalidad certificar y registrar de forma exhaustiva el proceso de **auditoría, reestructuración y saneamiento de los diagramas del sistema** llevado a cabo con anterioridad al inicio de la codificación del **Módulo 2 (Atención al Cliente por WhatsApp y Gestión de Conversaciones)**.

Siguiendo las buenas prácticas de la ingeniería de software y las directrices metodológicas de la cátedra de Seminario de Integración, antes de escribir una sola línea de código en el backend (Laravel / MariaDB) se procedió a contrastar los diagramas existentes contra:
1. La especificación formal de los **Requerimientos Funcionales (RF-10 al RF-19)** definidos en el *Informe del Sistema*.
2. El **marco teórico base de la carrera**:
   - *Programación Orientada a Objetos (POO Mes 1 a 4)*: Abstracción, encapsulamiento, ocultamiento de información y modelado puro de objetos (eliminación de anti-patrones relacionales dentro del modelo de dominio).
   - *Bases de Datos I*: Distinción clara entre el Modelo Entidad-Relación (DER Lógico/Físico) y el Diagrama de Clases UML.
   - *Modelado de Sistemas (UML 2.5)*: Semántica rigurosa de relaciones `«include»`, `«extend»`, generalizaciones y arquitectura BCE (*Boundary-Control-Entity*).
3. La **homogeneidad del proyecto**: Garantizar que el Módulo 2 mantenga la misma estructura visual, nivel de abstracción y calidad formal que los Módulos 1 (Clientes y Facturación Base) y 6 (Usuarios y Accesos).

---

## 2. Diagnóstico Inicial del Estado del Proyecto

Al sincronizar y revisar los artefactos desarrollados previamente para el Módulo 2, se detectaron discrepancias conceptuales y metodológicas tanto en los diagramas generales del sistema como en la propuesta específica del módulo:

### A. En el Diagrama de Clases General
* **Anti-patrón de persistencia relacional en el dominio**: Múltiples clases del dominio contenían atributos que representaban claves foráneas tabulares (`idCliente`, `idPlan`, `idServicio`, `idPago`, `idConversacion`, `idUsuario`, `idCuenta`). En el paradigma de orientación a objetos puro, un objeto no almacena una "clave foránea entera", sino que mantiene una **asociación (referencia)** tipada hacia el objeto relacionado.
* **Jerarquía de herencia incompleta**: La clase `NotificacionService` existía aislada sin reflejar su especialización respecto a la abstracción `«abstract» ServicioExterno`, rompiendo el principio de polimorfismo.

### B. En el Diagrama de Casos de Uso General
* **Casos de uso huérfanos / desconectados**: Varios casos de uso de los Módulos 1, 2, 4 y 6 se encontraban dibujados en el lienzo pero sin asociaciones a sus actores correspondientes ni relaciones de inclusión/extensión, quedando sin trazabilidad formal.
* **Inversión semántica de relaciones UML**: La relación entre el caso de uso base *Generar reportes* y su extensión *Exportar reportes a PDF/Excel* se encontraba con la flecha invertida (la flecha de un `«extend»` debe apuntar siempre desde el caso de uso que agrega comportamiento opcional hacia el caso de uso base).
* **Convivencia de alcances en los diagramas generales**: El diagrama general incluye funciones de otros módulos, entre ellas OCR y conciliación. Se conservan porque describen el sistema completo, pero quedan delimitadas explícitamente respecto del alcance RF-10 a RF-19 del Módulo 2.

### C. En los Diagramas Específicos del Módulo 2
* **Fragmentación excesiva y falta de estándar unificado**: El trabajo inicial del compañero presentaba 6 diagramas de secuencia individuales y un caso de uso específico en archivos XML/PNG separados, con dimensiones, paletas de colores y estilos tipográficos heterogéneos que rompían el estándar de lámina única consolidada presente en los Módulos 1 y 6.

---

## 3. Modificaciones Aplicadas en los Diagramas Generales

Para subsanar las desviaciones detectadas y establecer una base sólida antes del desarrollo, se ejecutaron las siguientes intervenciones estructurales:

### 3.1. Ajustes en `Diagrama de clases.drawio`

1. **Saneamiento a Paradigma de Objetos Puro**:
   * Se eliminaron los atributos de clave foránea relacional de las siguientes clases:
     - `Plan`: se removieron atributos de persistencia innecesarios.
     - `Servicio`: se eliminaron `idCliente` e `idPlan`, respaldando la relación mediante las asociaciones directas con `Cliente` y `Plan`.
     - `Cuota`: se eliminó `idServicio`.
     - `Pago`: se eliminaron `idCuota` e `idCuenta`.
     - `Conversacion`: se eliminó `idCliente`, modelando la referencia navegable a la entidad `Cliente`.
     - `Mensaje`: se eliminó `idConversacion`.
     - `Ticket`: se eliminaron `idConversacion`, `idServicio` e `idEmpleado`.
     - `NotaInterna`: se eliminaron claves foráneas internas.
   * *Justificación Teórica*: En POO (Clase 01 a 04), la composición y agregación se representan mediante asociaciones UML con multiplicidad/cardinalidad (ej. `1` a `0..*`), mientras que las claves primarias y foráneas pertenecen estrictamente al Modelo Relacional (Base de Datos I).
2. **Incorporación de Generalización**:
   * Se creó la flecha de herencia (`r26`) desde `NotificacionService` hacia `«abstract» ServicioExterno`, asegurando que los mecanismos de envío de WhatsApp y notificaciones hereden la firma y responsabilidades base del sistema para servicios externos.
3. **Validación de Entidades y Servicios de Módulo 2**:
   * Se validó que las clases de dominio (`Conversacion`, `Mensaje`, `Ticket`, `ComprobantePago`, `NotaInterna`) cuenten con sus métodos de negocio (`marcarLeido()`, `asignarOperador()`, `cerrar()`, `tipificar()`, `validarComprobante()`) alineados con la lógica que se codificará en los modelos Eloquent de Laravel.

### 3.2. Ajustes en `Diagrama Caso de uso general.drawio`

1. **Reconexión y Eliminación de Casos de Uso Huérfanos**:
   * Se conectaron todos los casos de uso flotantes a sus respectivos actores (`Cliente`, `Empleado`, `Administrador`, `WhatsApp Cloud API`, `Reloj del Sistema`).
2. **Corrección de Relaciones Invertidas**:
   * Se ajustó el conector `ei10` (`«extend»`) para que apunte formalmente desde `uc-export` (*Exportar reportes*) hacia `uc-reports` (*Generar reportes*), con la condición de extensión `[formato solicitado]`.
3. **Integración Completa del Módulo 2**:
   * Se incorporaron y enlazaron los casos de uso clave del Módulo 2 dentro del límite de sistema:
     - Recepción y registro de mensajes (`WhatsApp Cloud API`).
     - Identificación y validación de cliente (`«include»`).
     - Clasificación y procesamiento de intención por IA.
     - Derivación a operador humano (`«extend»`).
     - Registro de reclamo técnico y generación de ticket (`«include»`).
     - Notificación automática de vencimientos (`Reloj del Sistema / Cron`).
4. **Delimitación respecto del Módulo 3**:
   * El diagrama general conserva los casos de uso del sistema completo. El caso de uso específico del Módulo 2 termina al recibir y resguardar el comprobante; OCR, validación y conciliación continúan identificados como responsabilidades del Módulo 3.

---

## 4. Evolución de los Diagramas Específicos del Módulo 2: Unificación y Consolidación

Para cumplir con la directiva metodológica del proyecto (mantener idéntico formato y criterio que los Módulos 1 y 6), se tomó la decisión arquitectónica de **unificar los diagramas del Módulo 2 en dos artefactos maestros consolidados**, eliminando la dispersión de 12 archivos PNG/XML sueltos:

### 4.1. `Diagrama de Caso de Uso Modulo 2.drawio`
* **Enfoque**: Lámina única y autocontenida que agrupa todos los casos de uso derivados de los requerimientos **RF-10 al RF-19**.
* **Estructura**:
  * **Límite de Sistema**: Rectángulo contenedor rotulado *"Módulo 2: Atención al Cliente por WhatsApp y Gestión de Conversaciones"*.
  * **Actores Modelados**:
    - `Cliente`: Actor primario iniciador de consultas, comprobantes y reclamos.
    - `WhatsApp Cloud API`: Actor sistema externo que entrega y recibe webhooks.
    - `Empleado / Administrador`: Operadores humanos de soporte y atención escalada.
    - `Reloj del Sistema / Cron`: Disparador temporal para envíos automáticos.
  * **Relaciones Formales**:
    - `«include»`: Identificación del cliente obligatoria previa a la atención; registro de ticket obligatorio en reclamo técnico.
    - `«extend»`: Derivación condicional a operador humano ante falta de comprensión de la IA o solicitud explícita del cliente.

### 4.2. `Diagrama de Secuencia Modulo 2.drawio`
* **Arquitectura BCE (Boundary - Control - Entity)**:
  Se aplicó rigurosamente la separación en tres capas para las líneas de vida (*lifelines*):
  1. **Frontera (*Boundary*)**: `:WebhookWhatsAppController` (ingreso de peticiones Meta) y `:PanelAtencionController` (interfaz del empleado).
  2. **Control (*Control*)**: `:ServicioConversaciones`, `:ServicioIdentificacion`, `:IAService`, `:NotificacionService`.
  3. **Entidad / Persistencia (*Entity*)**: `:MariaDB (conversaciones, mensajes, tickets, clientes)`.
* **Fragmentos Combinados UML**:
  Se utilizaron marcos de interacción estandarizados:
  - `loop`: Para el procesamiento repetitivo de mensajes en la conversación.
  - `alt`: Para bifurcar los flujos según el *intent* detectado por la IA:
    - Rama 1: Consulta de estado de cuenta y deuda (RF-13).
    - Rama 2: Registro de reclamo técnico y ticket (RF-14).
    - Rama 3: Recepción y resguardo de comprobante de pago (RF-19).
    - Rama 4: Escalado a operador humano, historial y cierre (RF-15, RF-16 y RF-17).
    - Rama 5: Tarea programada (Cron) para notificación de vencimientos (RF-18).

---

## 5. Matriz de Trazabilidad: Requerimientos Funcionales vs. Diagramas Modificados

| Requerimiento Funcional | Descripción del Requisito | Reflejo en Diagrama de Clases | Reflejo en Caso de Uso M2 | Reflejo en Secuencia M2 |
| :--- | :--- | :--- | :--- | :--- |
| **RF-10** | Recepción y respuesta automática de mensajes vía WhatsApp | Métodos en `Conversacion` y `Mensaje` | `CU-M2-01: Recibir y registrar mensaje` | Recepción Webhook, parseo de JSON y persistencia inicial |
| **RF-11** | Reconocimiento de intención por IA | Clase `IAService::analizarIntencion()` | `CU-M2-03: Procesar intención con IA` | Invocación al servicio de IA y ramificación en marco `alt` |
| **RF-12** | Identificación del cliente por teléfono o DNI | Clase `ServicioIdentificacion` asociada a `Cliente` | `CU-M2-02: Identificar cliente` (`«include»`) | Búsqueda por número en `Cliente::where('telefono')` |
| **RF-13** | Consulta de estado de cuenta y deuda vía bot | Navegabilidad `Cliente -> Servicio -> Cuota` | `CU-M2-04: Consultar estado de cuenta` | Rama `alt [intención = consulta_saldo]` con lectura en DB |
| **RF-14** | Registro de reclamo y ticket de soporte técnico | Clase `Ticket` asociada a `Conversacion` y `Servicio` | `CU-M2-05: Registrar reclamo y generar ticket` | Rama `alt [intención = reclamo_tecnico]`, creación en tabla `ticket` |
| **RF-15** | Escalado y toma de control por un usuario interno | Atributos de asignación y modo humano en `Conversacion` | `CU-M2-07: Derivar a operador humano` | Segundo fallo o solicitud explícita, cola del panel y toma exclusiva |
| **RF-16** | Registro y consulta del historial completo | Clases `Conversacion` y `Mensaje` | Todos los casos que intercambian mensajes | Persistencia de mensajes entrantes, salientes, internos y notificaciones |
| **RF-17** | Cierre de la conversación | Método `Conversacion::cerrar()` y panel de atención | `CU-M2-08: Gestionar y cerrar conversación` | Cierre por el usuario responsable con fecha de finalización |
| **RF-18** | Envío automático de avisos de vencimiento | `ServicioNotificacionesVencimiento` y tarea programada | `CU-M2-09: Enviar recordatorio` | Scheduler -> consulta cuotas -> plantilla de WhatsApp con idempotencia |
| **RF-19** | Recepción y resguardo de comprobante | `Comprobante` asociado a cliente, conversación y mensaje | `CU-M2-06: Recibir y archivar comprobante` | Descarga de imagen o PDF, validación de formato y almacenamiento privado |

---

## 6. Conclusión y Visto Bueno para la Codificación

Gracias al saneamiento ejecutado:
1. **Se eliminaron todos los vicios y contradicciones de diseño** (desaparición de FKs relacionales en el modelo conceptual de clases y corrección de flechas de casos de uso).
2. **Se unificó el criterio estético y metodológico del proyecto**, presentando el Módulo 2 con el mismo rigor y formato que los Módulos 1 y 6.
3. **Se delimitó el alcance de `Modulo2Actualizacion`** en RF-10 a RF-19, manteniendo en los diagramas generales la visión integral necesaria para la futura integración con el Módulo 3.

**Dictamen Técnico:**  
El sistema cuenta con una base de diseño formal, consistente y validada. **Se otorga el visto bueno para dar inicio a la implementación del código fuente en Laravel**, comenzando por las migraciones de base de datos de las tablas `conversacion`, `mensaje`, `ticket`, `comprobante` y `nota_interna`.

## 8. Actualización posterior a la implementación

El código del Módulo 2 implementa actualmente los RF-10 a RF-19. La recepción de comprobantes comparte con el Módulo 3 una migración base compatible y agrega el contexto de WhatsApp mediante una migración separada. El servicio de recepción del Módulo 2 finaliza con el archivo resguardado y el estado pendiente; OCR, conciliación y aprobación continúan perteneciendo al Módulo 3.
