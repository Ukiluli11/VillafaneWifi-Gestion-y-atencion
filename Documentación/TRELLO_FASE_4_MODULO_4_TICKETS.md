# Estructura de Trello para la Fase 4: Módulo 4 (Reflejo Fiel del Diagrama de Gantt)

**Sistema:** Villafañe Wifi (Gestión y Atención al Cliente)  
**Cátedra:** Seminario de Integración (2026) — Licenciatura en Sistemas de Información, UCP Sede Formosa  
**Equipo:** Belazquez Agustín Lautaro - Serrano Ulises Iván  
**Fuente de Planificación:** Diagrama de Gantt Oficial (Tareas 59 a 70) e Informe del Sistema (RF-27 a RF-33)  

---

## 📌 Criterio de Organización

Siguiendo el estándar exacto aplicado en las **Fases 1, 2 y 3**:
* Las tarjetas agrupan las **tareas oficiales del Diagrama de Gantt**.
* Se utiliza la nomenclatura estándar: `[Fase X - Módulo Y] Nombre descriptivo (RF-A a RF-B)`.
* Cada tarjeta contiene su **`🎯 Objetivo:`** y su **Checklist con las tareas literales del Gantt**.
* Refleja **exclusivamente las tareas de desarrollo, diagramas, pruebas y entregables de la cátedra**.

---

## 🟥 FASE 4: Módulo 4 – Soporte Técnico y Gestión de Tickets
> **Período en Diagrama de Gantt:** 03/10/2026 al 16/10/2026 (Tareas 59 a 70).  
> **Hito de Cierre:** Culmina en el **Avance 3 (16/10/2026)** con el módulo de soporte y tickets operativo, integrado con el bot de WhatsApp y el panel web.  
> **Ubicación en Trello:** Lista *Por Hacer / En Progreso / Hecho*.

---

### 26. [Fase 4 - Módulo 4] Revisión UML y Ciclo de Vida de Tickets
**🎯 Objetivo:** Auditar las especificaciones técnicas y diseñar los diagramas de soporte técnico (casos de uso, secuencia y clases) formalizando el ciclo de vida de los incidentes.
**🛠️ Tareas de Preparación:**
- [ ] Tarea 59: Revisión de diagramas de casos de uso, secuencia y clases de soporte (M4)
- [ ] Tarea 60: Análisis del flujo y ciclo de vida de tickets de soporte (pendiente, en proceso, resuelto, cerrado, reabierto)

---

### 27. [Fase 4 - Módulo 4] Generación y Bandeja Web de Tickets (RF-27, RF-28)
**🎯 Objetivo:** Permitir la creación de tickets automáticos desde el bot de WhatsApp o manuales desde el panel, proveyendo una bandeja centralizada con filtros avanzados.
**🛠️ Requerimientos Desarrollados:**
- [ ] RF-27: Generación de tickets desde bot o manualmente desde panel
- [ ] RF-28: Visualización, búsqueda y filtrado de tickets en panel web (por estado, técnico, cliente y fecha)

---

### 28. [Fase 4 - Módulo 4] Asignación y Gestión de Estados del Ticket (RF-29, RF-30)
**🎯 Objetivo:** Implementar la asignación de tickets a operadores/técnicos y el control de transiciones de estado para auditoría y tiempos de resolución.
**🛠️ Requerimientos Desarrollados:**
- [ ] RF-29: Asignación automática y manual de tickets a técnicos
- [ ] RF-30: Gestión de estados de ticket: pendiente, en proceso, resuelto y cerrado

---

### 29. [Fase 4 - Módulo 4] Reapertura de Incidentes y Notas Internas (RF-31, RF-32)
**🎯 Objetivo:** Habilitar el flujo de reapertura ante problemas no solucionados y permitir a los técnicos asentar notas y bitácoras internas que resguarden la trazabilidad.
**🛠️ Requerimientos Desarrollados:**
- [ ] RF-31: Mecanismo de reapertura de tickets no solucionados
- [ ] RF-32: Registro de notas técnicas y comentarios internos (modelo NotaInterna)

---

### 30. [Fase 4 - Módulo 4] Trazabilidad con Chat de WhatsApp y Pruebas (RF-33)
**🎯 Objetivo:** Enlazar el ticket con el historial de chat de WhatsApp del cliente para contexto técnico inmediato y ejecutar las pruebas funcionales del módulo.
**🛠️ Requerimientos y Validación:**
- [ ] RF-33: Visualización de la conversación de chat asociada al ticket
- [ ] Tarea 68: Pruebas funcionales y unitarias de gestión, derivación y resolución de tickets

---

### 31. [Fase 4 - Módulo 4] Documentación Técnica y Manuales (Cierre Avance 3)
**🎯 Objetivo:** Consolidar la documentación del módulo, manual de usuario y material audiovisual para la presentación del Avance 3.
**🧪 Entregables Finales:**
- [ ] Tarea 69: Documentación técnica del módulo de soporte y tickets (M4)
- [ ] Tarea 70: Manual de usuario y video tutorial de soporte – Avance 3
