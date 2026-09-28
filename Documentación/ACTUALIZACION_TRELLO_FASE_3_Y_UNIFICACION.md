# Actualización Integral del Tablero Trello: Sistema Villafañe Wifi
## Sincronización Post-Desarrollo y Unificación de Fase 3 (Módulos 2 y 3)

**Sistema:** Villafañe Wifi (Gestión y Atención al Cliente)  
**Cátedra:** Seminario de Integración (2026) — Licenciatura en Sistemas de Información, UCP Sede Formosa  
**Docente:** Dr. Cristian Fernando Cerquand  
**Equipo de Trabajo:** Belazquez Agustín Lautaro - Serrano Ulises Iván  
**Rama Git Sincronizada:** `union-m2-m3`  
**Referencia del Tablero Trello:** [Tablero Trello Villafañe Wifi](https://trello.com/b/ns29k40w/sistema-para-villafane-wifi)  
**Fuente Metodológica:** Diagrama de Gantt oficial (PNG), Informe del Sistema (RF-01 a RF-46) y Estándar de Formato de Fase 1 y 2.

---

## 📌 Guía de Convenciones y Estructura del Tablero

Para respetar el estándar establecido durante las Fases 1 y 2, las tarjetas mantienen la siguiente estructura estricta:
1. **Título de Tarjeta (Épica):** `[Fase X - Módulo Y] Nombre descriptivo (RF-A a RF-B)`
2. **Descripción:** `**🎯 Objetivo:** [Meta concreta de negocio y técnica]`
3. **Checklists con Emojis Tipados:**
   * `📋 Tareas de Documentación y Diseño:`
   * `🛠️ Tareas de Desarrollo:` / `🛠️ Requerimientos Desarrollados:`
   * `🧪 Pruebas, Validación y Entregables:`
4. **Redacción de Tareas:** Siempre en **verbo en infinitivo** (*Implementar, Programar, Diseñar, Configurar, Validar, Redactar*).
5. **Estado de Casillas:**
   * `[x]` Tarea finalizada, testeada y comiteada.
   * `[ ]` Tarea pendiente o en curso.

---

# 📋 ESTADO GENERAL DE LAS LISTAS DEL TABLERO TRELLO

```text
┌──────────────────────────────────────┐  ┌─────────────────────────┐  ┌─────────────────────────────────────────┐
│        LISTA 1: HECHO (DONE)         │  │ LISTA 2: EN PROCESO     │  │        LISTA 3: POR HACER (BACKLOG)     │
├──────────────────────────────────────┤  ├─────────────────────────┤  ├─────────────────────────────────────────┤
│ • Tarjetas 1 a 4 (Fase 1: Docs)      │  │ • (Actualmente vacía o  │  │ • Tarjetas 23 a 26 (Fase 4: Módulo 4 -  │
│ • Tarjetas 5 a 13 (Fase 2: M1 y M6)  │  │   preparando inicio de  │  │   Soporte Técnico y Tickets)            │
│ • Tarjetas 14 a 19 (Fase 3: Módulo 2)│  │   Fase 4 - Módulo 4)    │  │ • Tarjetas 27 a 29 (Fase 4: Módulo 5 -  │
│ • Tarjetas 20 a 24 (Fase 3: Módulo 3)│  │                         │  │   Reportes y Dashboard)                 │
│ • Tarjeta 25 (Fase 3: Unificación    │  │                         │  │ • Tarjetas 30 a 32 (Fase 5: Cierre Final│
│   e Integración M2 + M3)             │  │                         │  │   y Auditoría Integral)                 │
└──────────────────────────────────────┘  └─────────────────────────┘  └─────────────────────────────────────────┘
```

---

# 🟩 LISTA: HECHO (DONE)

> **Nota:** Las tarjetas de la **Fase 1 (1 a 4)** y de la **Fase 2 (5 a 13)** ya se encontraban completadas tras la Entrega 1 con nota (04/09/2026). A continuación se incorporan las tarjetas finalizadas de la **Fase 3 (Módulo 2, Módulo 3 y Unificación)** que deben moverse/crearse en la columna **Hecho**.

---

### 14. [Fase 3 - Módulo 2] Arquitectura del Bot de WhatsApp y Configuración API
**🎯 Objetivo:** Diseñar la arquitectura conversacional, auditar los diagramas UML del bot y establecer la comunicación bidireccional con la Cloud API de Meta.
**🛠️ Tareas de Preparación y Arquitectura:**
- [x] Revisar y auditar los diagramas de Casos de Uso, Secuencia y Clases específicos del Módulo 2
- [x] Configurar credenciales y variables de entorno en Laravel (`META_WHATSAPP_TOKEN`, `META_PHONE_NUMBER_ID`, `META_WEBHOOK_VERIFY_TOKEN`)
- [x] Programar el controlador `WebhookWhatsappController` con validación de token `hub.challenge` y verificación de firma criptográfica SHA-256
- [x] Desarrollar la capa cliente `WhatsAppService` para el envío de plantillas y mensajes de texto libres vía HTTPS
- [x] Crear el simulador web interactivo (`SimuladorWhatsappController`) para pruebas funcionales locales sin depender de la red de Meta

---

### 15. [Fase 3 - Módulo 2] Recepción, Reconocimiento de Intención con IA e Identificación (RF-10, RF-11, RF-12)
**🎯 Objetivo:** Recibir los mensajes entrantes, analizar la intención del usuario mediante procesamiento de lenguaje natural y asociar la línea con un cliente registrado.
**🛠️ Requerimientos Desarrollados:**
- [x] **RF-10:** Programar la recepción y respuesta automática de mensajes vía WhatsApp mediante el bot conversacional
- [x] **RF-11:** Integrar el servicio `IAService` para el reconocimiento de intenciones en lenguaje natural (`consulta_saldo`, `reclamo_tecnico`, `envio_comprobante`, `atencion_humana`)
- [x] **RF-11:** Implementar mecanismo de fallback: solicitud de reformulación ante ambigüedad y derivación automática tras el segundo intento fallido
- [x] **RF-12:** Programar el servicio `ServicioIdentificacion` para vincular automáticamente el teléfono emisor con la entidad `Cliente` o solicitar DNI
- [x] **RF-12:** Desarrollar respuesta predeterminada ofreciendo contratación y alta a números no registrados en la base de datos

---

### 16. [Fase 3 - Módulo 2] Consultas Financieras y Registro de Reclamos vía Bot (RF-13, RF-14)
**🎯 Objetivo:** Permitir al abonado consultar su estado contable y asentar incidentes técnicos de manera autoservicio desde el chat.
**🛠️ Requerimientos Desarrollados:**
- [x] **RF-13:** Programar en `ServicioCuentaCorriente` la consulta automatizada de deuda, cuotas pendientes, importe total y fecha de vencimiento
- [x] **RF-13:** Formatear respuesta amigable por WhatsApp con el desglose del estado del servicio de internet del cliente
- [x] **RF-14:** Programar el flujo conversacional interactivo para el registro guiado de reclamos de conectividad o lentitud
- [x] **RF-14:** Implementar `ServicioTickets` y crear automáticamente el registro en la tabla `ticket` con estado `abierto` y tipo `tecnico`, informando el número de seguimiento al cliente

---

### 17. [Fase 3 - Módulo 2] Escalado a Operador Humano, Historial de Chat y Cierre (RF-15, RF-16, RF-17)
**🎯 Objetivo:** Facilitar la transición de la conversación hacia operadores humanos, persistir cada interacción y gestionar el ciclo de vida de la sesión.
**🛠️ Requerimientos Desarrollados:**
- [x] **RF-15:** Desarrollar `ServicioAtencionHumana` para transferir la conversación a modo manual (`modo_atencion = 'humano'`)
- [x] **RF-15:** Diseñar la bandeja y panel web en Blade (`resources/views/conversaciones`) para que los empleados respondan en vivo desde el navegador
- [x] **RF-16:** Crear migraciones y modelos `Conversacion` y `Mensaje` para almacenar cada mensaje entrante/saliente con fecha, emisor y estado
- [x] **RF-17:** Implementar la lógica de cierre de sesión formal tanto por el bot al terminar una gestión como por el empleado al resolver la consulta

---

### 18. [Fase 3 - Módulo 2] Notificaciones Push Masivas de Vencimiento (RF-18)
**🎯 Objetivo:** Automatizar el envío de alertas de vencimiento preventivas y por mora a través de WhatsApp.
**🛠️ Requerimientos Desarrollados:**
- [x] **RF-18:** Desarrollar el servicio `ServicioNotificacionesVencimiento` para consultar servicios activos con cuotas próximas a vencer o vencidas
- [x] **RF-18:** Programar el comando de consola de Artisan `php artisan wifi:notificar-vencimientos` para ejecución programada (Cron)
- [x] **RF-18:** Registrar el mensaje saliente en el historial conversacional del cliente para auditoría de notificaciones enviadas

---

### 19. [Fase 3 - Módulo 2] Pruebas, Documentación y Cierre de Módulo 2 (Avance 2)
**🎯 Objetivo:** Consolidar los diagramas, manuales y validaciones de software del Módulo 2 para el cumplimiento formal del Avance 2.
**🧪 Pruebas, Validación y Entregables:**
- [x] Unificar los diagramas fragmentados en láminas maestras consolidadas: `Diagrama de Caso de Uso Modulo 2.drawio` y `Diagrama de Secuencia Modulo 2.drawio` (BCE)
- [x] Redactar el informe de auditoría y evolución de diagramas `INFORME_MODIFICACIONES_Y_EVOLUCION_DIAGRAMAS_MODULO_2.md`
- [x] Elaborar el Manual de Usuario para la operación del bot y panel de conversaciones de WhatsApp
- [x] Grabar video tutorial y demostración funcional del bot interactivo (Avance 2)
- [x] Ejecutar suite de pruebas funcionales de webhooks, NLP y respuestas del bot

---

### 20. [Fase 3 - Módulo 3] Arquitectura de Pagos, Conciliación y Revisión UML (M3)
**🎯 Objetivo:** Auditar las especificaciones del modelo de pagos, descontaminar el diagrama de clases de atributos relacionales y diseñar la base de datos de conciliación.
**🛠️ Tareas de Preparación y Arquitectura:**
- [x] Elaborar el Diagrama de Caso de Uso específico: `Diagrama de Caso de Uso Modulo 3.drawio`
- [x] Elaborar el Diagrama de Secuencia específico: `Diagrama de Secuencia Modulo 3.drawio` con arquitectura BCE y bloques `alt` de aprobación/rechazo
- [x] Descontaminar de claves foráneas tabulares las clases del dominio en `Diagrama de clases.drawio` adoptando POO puro
- [x] Crear la migración de base de datos `2026_09_27_120000_create_comprobante_table.php` con índices contables
- [x] Redactar el informe técnico previo de diseño `Informe_Modificaciones_Diagramas_Previo_Modulo3.md`

---

### 21. [Fase 3 - Módulo 3] Ingesta Multimedia, Motor OCR y Detección de Duplicados (RF-19 a RF-22)
**🎯 Objetivo:** Recibir comprobantes de transferencia bancaria, procesar el archivo mediante OCR y prevenir fraudes por duplicación.
**🛠️ Requerimientos Desarrollados:**
- [x] **RF-19:** Habilitar la recepción, validación de tipo MIME (JPEG, PNG, PDF) y resguardo seguro en almacenamiento de comprobantes de pago
- [x] **RF-20:** Desarrollar el servicio `OCRService` para la extracción automatizada de datos clave: monto transferido, fecha de la operación y número de transacción
- [x] **RF-21:** Programar el control de calidad OCR con cálculo de confianza y solicitud automática de reenvío ante imágenes borrosas o incompletas
- [x] **RF-22:** Implementar el algoritmo de huella digital criptográfica SHA-256 (`Comprobante::esDuplicado()`) para alertar e impedir el procesamiento de comprobantes duplicados

---

### 22. [Fase 3 - Módulo 3] Bandeja Web de Conciliación y Validación de Pagos (RF-23, RF-24)
**🎯 Objetivo:** Proveer una interfaz administrativa web para que los operadores comparen los datos extraídos por el OCR contra la imagen original y decidan el destino del comprobante.
**🛠️ Requerimientos Desarrollados:**
- [x] **RF-23:** Diseñar la vista web `resources/views/comprobantes/index.blade.php` con filtros por estado (`pendiente`, `aprobado`, `rechazado`), paginación y buscador
- [x] **RF-23:** Desarrollar la pantalla de detalle `resources/views/comprobantes/detalle.blade.php` con visor embebido de comprobante e historial de cuotas
- [x] **RF-24:** Programar en `ComprobanteController::aprobar()` la confirmación del pago con vinculación a cuenta receptora y medio de pago
- [x] **RF-24:** Programar en `ComprobanteController::rechazar()` el rechazo justificado con registro mandatorio del motivo (ej. comprobante apócrifo, monto insuficiente, cuenta errónea)
- [x] Implementar funcionalidad complementaria de carga manual de comprobantes desde el panel web (`SubirComprobanteRequest`)

---

### 23. [Fase 3 - Módulo 3] Imputación Contable en Cascada y Notificación Saliente (RF-25, RF-26)
**🎯 Objetivo:** Impactar financieramente los comprobantes aprobados cancelando cuotas pendientes y notificar el resultado en tiempo real al abonado.
**🛠️ Requerimientos Desarrollados:**
- [x] **RF-25:** Desarrollar `ServicioFacturacion::imputarPagoACuotas()` bajo transacción ACID (`DB::transaction`) para crear la entidad `Pago`
- [x] **RF-25:** Programar el algoritmo de liquidación contable en cascada: imputar el monto ingresado cancelando las cuotas más antiguas primero
- [x] **RF-25:** Actualizar automáticamente el estado de la cuota a `pagada`, saldo en cuenta corriente y fecha de pago
- [x] **RF-26:** Desarrollar `NotificacionService` para despachar automáticamente un mensaje de WhatsApp informando la acreditación exitosa con detalle de cuotas saldadas
- [x] **RF-26:** Notificar por WhatsApp ante rechazo de comprobante indicando el motivo para que el cliente pueda subsanarlo

---

### 24. [Fase 3 - Módulo 3] Pruebas Automatizadas, Documentación y Cierre de Módulo 3 (Entrega 2)
**🎯 Objetivo:** Asegurar la cobertura de pruebas, consistencia contable y entregables formales de la Entrega 2 con nota.
**🧪 Pruebas, Validación y Entregables:**
- [x] Desarrollar la suite de pruebas unitarias y de integración `ComprobantesTest.php` y `ServiciosFacturacionTest.php`
- [x] Validar la regla de negocio de imputación cronológica más antigua primero mediante tests automatizados
- [x] Redactar el Manual de Usuario de la Bandeja de Conciliación de Pagos y Comprobantes
- [x] Grabar video tutorial y demostración del circuito completo de pago y validación contable
- [x] Preparar paquete documental consolidado para la Entrega 2 con nota (02/10/2026)

---

### 25. [Fase 3 - Integración] Fusión y Saneamiento Integral Módulos 2 y 3 (`union-m2-m3`)
**🎯 Objetivo:** Unificar en una sola rama maestra de trabajo el código de backend y los diagramas del bot de WhatsApp (M2) y la conciliación contable (M3), resolviendo los conflictos de integración.
**🛠️ Tareas de Integración y Saneamiento:**
- [x] Fusionar las migraciones de comprobante (`create_comprobante_table` + `add_whatsapp_context_to_comprobante_table`) permitiendo comprobantes originados en WhatsApp y en el panel web
- [x] Consolidar el modelo `Comprobante.php` con sus 4 relaciones activas (`cliente`, `pago`, `conversacion`, `mensaje`)
- [x] Subsanar el error fatal en `Pago.php`: eliminación de la declaración duplicada de `public function comprobante(): BelongsTo` en la línea 45
- [x] Corregir la sintaxis de interpolación obsoleta en `PagoController.php` para compatibilidad con PHP 8.2+
- [x] Reasociar y conectar en `Diagrama Caso de uso general.drawio` los 4 casos de uso huérfanos del M3 (`uc-ocr`, `uc-account`, `uc-installments`, `uc-bank`) mediante 7 conectores formales UML
- [x] Ejecutar la suite completa de pruebas de Laravel con 34 tests aprobados al 100% (114 aserciones, 0 errores)
- [x] Redactar el documento formal de auditoría y trazabilidad `INFORME_UNIFICACION_Y_CORRECCIONES_MODULOS_2_Y_3.md`
- [x] Sincronizar y pushear exitosamente la rama limpia a GitHub (`origin/union-m2-m3`)

---

# 📅 LISTA: POR HACER (BACKLOG - FASES SIGUIENTES)

> Las siguientes tarjetas corresponden a las fases estipuladas en el Diagrama de Gantt oficial para los próximos hitos evaluativos. Quedan redactadas bajo el mismo estándar formal para ser tomadas en la siguiente etapa.

---

### 26. [Fase 4 - Módulo 4] Análisis, Arquitectura y Diagramación de Soporte Técnico (M4)
**🎯 Objetivo:** Modelar el ciclo de vida de los incidentes técnicos y auditar los diagramas de soporte antes de codificar.
**📋 Tareas de Preparación y Diseño:**
- [ ] Diseñar el Diagrama de Casos de Uso específico del Módulo 4 (`Diagrama de Caso de Uso Modulo 4.drawio`)
- [ ] Diseñar el Diagrama de Secuencia específico del Módulo 4 (`Diagrama de Secuencia Modulo 4.drawio`) bajo patrón BCE
- [ ] Validar las asociaciones de las entidades `Ticket` y `NotaInterna` en el `Diagrama de clases.drawio`
- [ ] Crear migraciones de base de datos para la tabla de asignaciones y notas internas de soporte

---

### 27. [Fase 4 - Módulo 4] Gestión Web de Tickets de Soporte Técnico (RF-27 a RF-33)
**🎯 Objetivo:** Implementar la mesa de ayuda (HelpDesk) interna para la atención, derivación y resolución de incidentes de clientes.
**🛠️ Requerimientos a Desarrollar:**
- [ ] **RF-27:** Habilitar la creación manual de tickets de servicio desde el panel web por empleados o administradores
- [ ] **RF-28:** Programar la vista web de bandeja de tickets con filtros por estado, prioridad, área y servicio afectado
- [ ] **RF-29:** Implementar el algoritmo de asignación de tickets a técnicos por orden de llegada y disponibilidad
- [ ] **RF-30:** Programar la máquina de estados del ticket: `abierto`, `en_atencion`, `resuelto`, `cerrado`
- [ ] **RF-31:** Desarrollar el mecanismo de reapertura de tickets si el problema de red persiste
- [ ] **RF-32:** Implementar el registro de notas técnicas y comentarios internos (`NotaInterna`) visibles solo para el personal
- [ ] **RF-33:** Vincular y permitir consultar desde el ticket la conversación de WhatsApp que originó el reclamo

---

### 28. [Fase 4 - Módulo 4] Pruebas, Documentación y Cierre de Módulo 4 (Avance 3)
**🎯 Objetivo:** Validar el sistema de soporte técnico y empaquetar los entregables formales para el Avance 3 (16/10/2026).
**🧪 Pruebas, Validación y Entregables:**
- [ ] Programar pruebas automatizadas de asignación, cambio de estados y notas internas de tickets
- [ ] Redactar la documentación técnica del módulo de soporte y actualizar el Informe del Sistema
- [ ] Elaborar el Manual de Usuario para el área de soporte técnico e instaladores
- [ ] Grabar video tutorial y demostración del ciclo de vida de un reclamo (Avance 3)

---

### 29. [Fase 4 - Módulo 5] Dashboard Ejecutivo y Reportes Estadísticos (RF-34 a RF-39)
**🎯 Objetivo:** Construir el panel de control con métricas en tiempo real e informes financieros y operativos con exportación.
**🛠️ Requerimientos a Desarrollar:**
- [ ] **RF-34:** Desarrollar el reporte interactivo de clientes activos al día vs. clientes en mora
- [ ] **RF-35:** Programar el reporte financiero de ingresos y cobranzas filtrable por rango de fechas (diario, semanal, mensual)
- [ ] **RF-36:** Programar el reporte estadístico de tickets de soporte por técnico, motivo y tiempo de resolución
- [ ] **RF-37:** Diseñar el panel de alertas operativas (cuotas vencidas, reclamos pendientes, comprobantes por conciliar)
- [ ] **RF-38:** Construir el Dashboard general interactivo con gráficos e indicadores clave de rendimiento (KPIs)
- [ ] **RF-39:** Implementar la exportación de reportes a formatos descargables PDF y Excel (.xlsx)

---

### 30. [Fase 4 - Módulo 5] Pruebas, Documentación y Entrega 3 con Nota
**🎯 Objetivo:** Validar la precisión de las métricas financieras y presentar la Entrega 3 (30/10/2026).
**🧪 Pruebas, Validación y Entregables:**
- [ ] Ejecutar pruebas de consistencia en cálculos matemáticos de reportes y agregaciones SQL
- [ ] Redactar la documentación técnica del módulo de reportes y dashboard
- [ ] Elaborar el Manual de Usuario para la gerencia y toma de decisiones
- [ ] Grabar video tutorial demostrativo del dashboard y exportación de reportes (Entrega 3)

---

### 31. [Fase 5] Consolidación, Auditoría Integral y Cierre del Sistema (Entrega Final)
**🎯 Objetivo:** Realizar la integración final de todos los módulos, pruebas end-to-end y empaquetado para la defensa de la materia (13/11/2026).
**🧪 Entregables de Cierre de Carrera:**
- [ ] Auditoría final de concordancia entre los 46 Requerimientos Funcionales, la base de datos MariaDB y el código Laravel
- [ ] Actualización y emprolijamiento de la totalidad de los diagramas UML del proyecto (DER, Clases, Casos de Uso, Secuencias, Despliegue)
- [ ] Pruebas integrales de extremo a extremo de todo el ciclo de negocio (Cliente $\rightarrow$ Bot $\rightarrow$ Ticket $\rightarrow$ Pago $\rightarrow$ Reporte)
- [ ] Consolidación del Informe Final del Sistema, Diccionario de Datos y Diccionario de Clases
- [ ] Redacción del Manual de Usuario Integral del Sistema
- [ ] Grabación del Video Final del Sistema en funcionamiento y preparación de la presentación de defensa ante el tribunal docente
