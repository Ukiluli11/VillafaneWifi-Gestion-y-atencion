# Villafañe Wifi — resumen del módulo 2 (17/10/2026)

## Alcance

El trabajo de hoy se concentró en el **módulo 2: atención y asistencia por WhatsApp**. Todo se hizo como **demostración**, con registros y montos ficticios. No se habilitaron clientes reales ni cobros reales. El módulo 3 queda para otra jornada.

## Qué quedó hecho y comprobado

- Se conectó la aplicación Laravel local con el número de prueba de WhatsApp de Meta. El webhook recibió mensajes y el bot respondió desde el sistema.
- Se probó el recorrido de una persona no identificada: `REGISTRARME`, documento, nombre, tipo de cliente, dirección, elección de plan e instalación. Se creó el servicio de prueba **#7**.
- Se comprobaron el menú, la consulta de cuenta sin deuda, el registro de un reclamo, la derivación a un operador y la respuesta humana desde el panel. También se probó la reformulación de mensajes desconocidos y la derivación tras dos intentos.
- Se completó el seguimiento de tickets en el panel: listado, asignación, notas internas, resolución, cierre y reapertura. El ticket de prueba **#2** se asignó, documentó y cerró. Las conversaciones de prueba también se cerraron desde su pantalla.
- Se agregó el envío de recordatorios con plantillas de Meta, control de destinatarios permitidos y prevención de duplicados. La tarea está programada a las **09:00, hora de Argentina**, mientras esté funcionando el programador de la aplicación.
- Se creó una cuota ficticia **#24**, por **$18.000**, vinculada al servicio #7, para probar un recordatorio. Su vencimiento de prueba es el **19/10/2026**; antes de usarla nuevamente hay que revisar fecha y estado.
- Se publicó y guardó en Meta la [política de privacidad de la demostración](https://villafane-wifi-privacidad.savoryslug33.chatgpt.site).
- Se creó el usuario de sistema **VillafaneWifi-Demo** en Meta, con acceso a la aplicación y a la cuenta de WhatsApp de prueba. Su identificador de acceso quedó configurado en el `.env` local y se comprobó que puede consultar los recursos necesarios. **No copiar ese identificador al repositorio ni a capturas.**
- Las pruebas enfocadas del módulo 2 y de tickets pasaron: **14 pruebas, 59 verificaciones** en la ejecución realizada hoy.

## Qué falta para dar por cerrado el módulo 2

1. **Esperar la aprobación de las plantillas** `recordatorio_vencimiento` y `recordatorio_cuota_vencida` (`es_AR`). En el último control figuraban pendientes; volver a consultar su estado en Meta.
2. Cuando estén aprobadas, **probar un recordatorio real solo al número autorizado para pruebas**. Revisar primero la cuota #24: según el día de la prueba corresponderá el aviso previo o el de cuota vencida. Confirmar recepción en WhatsApp y registro correcto en el panel, sin enviar duplicados.
3. Hacer una **revisión final del módulo 2**: ejecutar sus pruebas, verificar que el webhook siga recibiendo mensajes después de reiniciar y registrar cualquier ajuste pendiente. La opción «Enviar comprobante» se puede revisar con el módulo 3, al que pertenece la conciliación del pago.

## Para retomarlo sin sorpresas

- **Hoy el sistema corre en la PC**: aplicación, MariaDB y programador de tareas. Apagarla detiene las respuestas y los recordatorios. Los datos locales permanecen en la base de datos.
- El enlace `trycloudflare.com` usado para el webhook es **temporal**. Si cambia al reiniciar el túnel, hay que poner la nueva URL `/webhooks/whatsapp` en Meta y pulsar **«Verificar y guardar»**. Se conserva el mismo identificador de verificación configurado localmente; no hace falta publicarlo aquí.
- La aplicación de Meta seguía **sin publicar**. La configuración actual está destinada a pruebas controladas; no presupone atención abierta a clientes reales.
- El archivo `.env` y la base de datos local **no se suben con Git**. Al instalar en otra PC o servidor habrá que configurar credenciales, migraciones y datos de demostración por separado. Evitar incluir tokens, claves o datos sensibles en commits.
- La política de privacidad está publicada. La carpeta `sites/` contiene una copia local; revisar si se quiere incluirla en el push. También hay archivos nuevos de tickets y cambios del módulo 2 pendientes de subir.
- Se evaluó alojar todo en Railway: técnicamente admite aplicación, base de datos y tareas programadas, pero el plan Hobby de **US$5/mes es un mínimo, no un precio final fijo**. Un límite de consumo puede apagar el sistema al alcanzarse. **No se contrató alojamiento**; conviene elegir y preparar uno con costo aceptable antes de cambiar el webhook a una dirección permanente. Referencias: [precios](https://docs.railway.com/pricing/plans) y [control de costos](https://docs.railway.com/pricing/cost-control).

## Próxima sesión

Primero comprobar el estado de las dos plantillas. Si ya están aprobadas, revisar la cuota ficticia y crear otra de prueba si la fecha ya no sirve; luego hacer la prueba de recordatorio. Después decidir el alojamiento estable y repetir una prueba completa de WhatsApp desde la nueva dirección.
