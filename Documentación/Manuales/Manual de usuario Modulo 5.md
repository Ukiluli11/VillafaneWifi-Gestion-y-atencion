# Manual de usuario del Módulo 5

Este módulo permite consultar la situación de los clientes, las cobranzas y el soporte técnico. Se accede desde **Reportes y Métricas** en el menú lateral o **Dashboard y Métricas** en el inicio.

## Acceso y permisos

Administrador y empleados de Administración pueden consultar y exportar reportes. Soporte y Atención al Cliente pueden consultar; los botones de descarga no aparecen para estos perfiles y el servidor rechaza una descarga directa sin permiso. Un usuario sin sesión debe iniciar sesión para acceder.

## Dashboard

El tablero muestra clientes activos, porcentaje y cantidad de clientes en mora, deuda vencida, recaudación del mes, tickets pendientes y comprobantes por validar. Los gráficos presentan ingresos de los últimos seis meses, distribución de servicios activos por plan y estados de tickets. Los valores se actualizan al cargar la página.

La morosidad se calcula sobre clientes con cuotas sin pago que ya vencieron. Una cuota que vence hoy se considera próxima a vencer durante ese día, según la hora de Argentina. La recaudación se obtiene de pagos registrados, no de comprobantes pendientes de validación.

## Clientes y morosidad

1. Abrir **Clientes y Morosidad**.
2. Seleccionar todos los clientes, únicamente al día o únicamente en mora.
3. Opcionalmente seleccionar un plan y buscar por nombre, documento o teléfono.
4. Pulsar **Filtrar** para actualizar el resumen y la tabla.
5. Usar **Cuenta** para consultar la cuenta corriente del cliente, según los permisos disponibles.
6. Pulsar **Limpiar** para quitar los filtros.

La deuda incluye cuotas vencidas sin pago. Los días de atraso corresponden a la cuota vencida más antigua y se presentan como días completos. Los totales del resumen corresponden a los clientes seleccionados por los filtros.

## Cobranzas e ingresos

1. Abrir **Cobranzas e Ingresos**.
2. Seleccionar **Hoy**, **Esta semana** o **Este mes**, o completar las fechas desde y hasta para otro período.
3. Opcionalmente seleccionar medio de pago y cuenta receptora.
4. Pulsar **Filtrar**.

El resumen muestra el total cobrado, cantidad de pagos, importe promedio y distribución por medio de pago. Los accesos rápidos de período conservan el medio y la cuenta seleccionados. La semana abarca de lunes a domingo. **Mes actual** vuelve a la consulta inicial.

## Rendimiento de tickets

1. Abrir **Rendimiento de Tickets**.
2. Elegir el rango de fechas, estado y responsable. La opción **Sin asignar** muestra tickets sin responsable.
3. Pulsar **Filtrar**.

El período se aplica a la fecha de creación del ticket. La tasa de resolución incluye tickets resueltos y cerrados del conjunto filtrado. El tiempo medio de resolución se calcula entre creación y resolución de los tickets que tienen ambas fechas; se expresa en horas. Las tarjetas por responsable resumen la carga de trabajo y los casos resueltos.

## Alertas de vencimiento

La bandeja muestra cuotas impagas vencidas y próximas a vencer en los siguientes siete días. El dashboard ofrece una selección de alertas con un horizonte de cinco días. Los importes se refieren a cuotas, por lo que un cliente puede aparecer varias veces.

**Disparar Aviso WhatsApp** solicita al servicio de notificaciones procesar esa cuota. El envío depende de la configuración de recordatorios, el teléfono permitido, el estado del servicio y las reglas para evitar avisos duplicados. El proceso automático está programado a las 09:00 de Argentina y requiere que el programador de tareas esté activo en el servidor. Un aviso omitido o registrado no garantiza que el mensaje haya sido entregado.

## Descargar PDF y Excel

1. Aplicar los filtros del reporte.
2. Pulsar **Descargar PDF** o **Exportar Excel (.xlsx)**.
3. Abrir el archivo descargado y comprobar el período y los registros.

Las descargas de morosidad, ingresos y tickets conservan los filtros de la pantalla. El PDF incluye encabezado institucional, fecha de emisión en Argentina, usuario responsable, resumen y detalle. El Excel contiene encabezados, importes numéricos y totales de deuda o ingresos, según el reporte. Los documentos y teléfonos se conservan como texto. La exportación CSV anterior continúa disponible para compatibilidad.

Si la tabla queda vacía, revisar los filtros y el período. Si falta una opción de descarga, verificar el perfil de acceso. Los datos fiscales y de contacto del membrete deben ser corroborados por el responsable del sistema antes de usar el informe como documentación externa.
