# Estructura de Trello para la Fase 3: Módulos 2 y 3 (Reflejo Fiel del Diagrama de Gantt)

**Sistema:** Villafañe Wifi (Gestión y Atención al Cliente)  
**Cátedra:** Seminario de Integración (2026) — Licenciatura en Sistemas de Información, UCP Sede Formosa  
**Equipo:** Belazquez Agustín Lautaro - Serrano Ulises Iván  
**Fuente de Planificación:** Diagrama de Gantt Oficial (Tareas 28 a 58) e Informe del Sistema (RF-10 a RF-26)  

---

## 📌 Criterio de Organización

Siguiendo el estándar exacto aplicado en la **Fase 1 y Fase 2**:
* Las tarjetas agrupan las **tareas oficiales del Diagrama de Gantt**.
* Se utiliza la nomenclatura estándar: `[Fase X - Módulo Y] Nombre descriptivo (RF-A a RF-B)`.
* Cada tarjeta contiene su **`**🎯 Objetivo:**`** y su **Checklist con las tareas literales del Gantt**.
* No se incluyen desvíos internos ni problemas de Git; refleja **exclusivamente las tareas de desarrollo, diagramas, pruebas y entregables de la cátedra**.

---

## 🟧 FASE 3: Módulo 2 (Bot WhatsApp con IA) y Módulo 3 (Conciliación de Pagos y OCR)
> **Período en Diagrama de Gantt:** 05/09/2026 al 02/10/2026 (Tareas 28 a 58).  
> **Estructura de la Fase en el Gantt:**
> * **Etapa 1 (Fase 3a - Módulo 2):** Culminó en el **Avance 2 (18/09/2026)** con demostración interactiva del bot de WhatsApp.
> * **Etapa 2 (Fase 3b - Módulo 3):** Culmina en la **Entrega 2 con nota (02/10/2026)** con el circuito completo de pagos, OCR y conciliación.  
> **Ubicación en Trello:** Lista *Hecho*.

---

### Sub-etapa 3A: Módulo 2 – Bot de Atención WhatsApp con IA (Avance 2)

### 14. [Fase 3 - Módulo 2] Revisión UML y Arquitectura del Bot
**🎯 Objetivo:** Revisar las especificaciones y diagramas previos al código y planificar la integración con la Cloud API de WhatsApp.
**🛠️ Tareas de Preparación:**
- [x] Revisión de diagramas de casos de uso, secuencia y clases del bot (M2)
- [x] Análisis de arquitectura del bot y configuración de Cloud API

### 15. [Fase 3 - Módulo 2] Recepción, NLP e Identificación (RF-10, RF-11, RF-12)
**🎯 Objetivo:** Programar el núcleo conversacional para responder mensajes, interpretar intención con IA e identificar al abonado.
**🛠️ Requerimientos Desarrollados:**
- [x] RF-10: Recepción y respuesta automática de mensajes vía WhatsApp
- [x] RF-11: Reconocimiento de intención en lenguaje natural con IA
- [x] RF-12: Identificación automática del cliente en la conversación

### 16. [Fase 3 - Módulo 2] Consultas y Registro de Reclamos (RF-13, RF-14)
**🎯 Objetivo:** Habilitar el autoservicio para que los clientes consulten deuda y generen tickets de reclamo técnico desde el chat.
**🛠️ Requerimientos Desarrollados:**
- [x] RF-13: Consulta de estado de cuenta corriente vía bot
- [x] RF-14: Registro de reclamos y solicitudes de soporte técnico

### 17. [Fase 3 - Módulo 2] Escalado Humano, Historial y Cierre (RF-15, RF-16, RF-17)
**🎯 Objetivo:** Derivar conversaciones a operadores cuando sea necesario, persistir el chat completo y gestionar el cierre de sesiones.
**🛠️ Requerimientos Desarrollados:**
- [x] RF-15: Escalado de conversación a usuario interno / operador
- [x] RF-16: Registro y persistencia del historial completo de chat
- [x] RF-17: Cierre formal de conversación por bot o empleado

### 18. [Fase 3 - Módulo 2] Notificación Automática de Vencimientos (RF-18)
**🎯 Objetivo:** Programar el envío proactivo de recordatorios de cuotas próximas a vencer o vencidas.
**🛠️ Requerimientos Desarrollados:**
- [x] RF-18: Notificación automática de vencimientos por WhatsApp

### 19. [Fase 3 - Módulo 2] Pruebas, Documentación y Videos (Cierre Avance 2)
**🎯 Objetivo:** Validar el comportamiento del bot y preparar los entregables formales para el Avance 2.
**🧪 Entregables Finales:**
- [x] Pruebas funcionales e integración con WhatsApp API
- [x] Documentación técnica del bot y flujos conversacionales (M2)
- [x] Manual de usuario – operación del bot y atención escalada
- [x] Video tutorial – atención de clientes vía WhatsApp bot
- [x] Video demostrativo del bot interactivo – Avance 2

---

### Sub-etapa 3B: Módulo 3 – Conciliación de Pagos y OCR (Entrega 2 con nota)

### 20. [Fase 3 - Módulo 3] Revisión UML y Diseño del Motor OCR
**🎯 Objetivo:** Auditar los diagramas de clases y secuencias de pagos y definir la arquitectura de lectura inteligente de comprobantes.
**🛠️ Tareas de Preparación:**
- [x] Revisión de diagramas de casos de uso, secuencia y clases de conciliación (M3)
- [x] Análisis y diseño del motor OCR y flujo de comprobantes

### 21. [Fase 3 - Módulo 3] Recepción de Comprobantes y Procesamiento OCR (RF-19, RF-20)
**🎯 Objetivo:** Recibir archivos multimedia de pago vía bot y extraer automáticamente los datos de la transferencia.
**🛠️ Requerimientos Desarrollados:**
- [x] RF-19: Recepción y envío de comprobantes vía WhatsApp bot
- [x] RF-20: Motor OCR: extracción automática de datos de comprobante

### 22. [Fase 3 - Módulo 3] Validación, Reenvío y Control de Duplicados (RF-21, RF-22)
**🎯 Objetivo:** Solicitar reenvío ante imágenes no legibles y bloquear intentos de fraude por duplicación de transferencias.
**🛠️ Requerimientos Desarrollados:**
- [x] RF-21: Mecanismo de solicitud de reenvío ante fallo de OCR
- [x] RF-22: Algoritmo de detección y alerta de comprobante duplicado

### 23. [Fase 3 - Módulo 3] Bandeja de Conciliación y Validación de Pagos (RF-23, RF-24)
**🎯 Objetivo:** Construir la interfaz administrativa web para auditar los comprobantes y confirmar o rechazar el cobro.
**🛠️ Requerimientos Desarrollados:**
- [x] RF-23: Bandeja web de conciliación y validación de pagos
- [x] RF-24: Interfaz de confirmación o rechazo de comprobante

### 24. [Fase 3 - Módulo 3] Impacto en Cuenta Corriente y Notificación (RF-25, RF-26)
**🎯 Objetivo:** Imputar el pago cancelando cuotas pendientes en cascada y notificar en tiempo real al abonado por WhatsApp.
**🛠️ Requerimientos Desarrollados:**
- [x] RF-25: Actualización automática de cuenta corriente y cuotas
- [x] RF-26: Notificación al cliente del resultado del pago por WhatsApp

### 25. [Fase 3 - Módulo 3] Pruebas, Documentación y Videos (Cierre Entrega 2)
**🎯 Objetivo:** Ejecutar las pruebas contables, consolidar la documentación técnica y generar los entregables para la Entrega 2 con nota.
**🧪 Entregables Finales:**
- [x] Pruebas de precisión OCR y validación contable
- [x] Documentación técnica de conciliación, OCR y actualización del informe (M3)
- [x] Manual de usuario – conciliación de pagos y comprobantes
- [x] Video tutorial – conciliación y lectura de comprobantes
- [x] Video demostrativo del proceso completo de pago – Entrega 2
