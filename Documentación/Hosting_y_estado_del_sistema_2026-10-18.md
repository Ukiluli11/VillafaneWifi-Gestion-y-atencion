# Villafañe Wifi: hosting y estado del sistema

Resumen del trabajo del **18/10/2026**, para compartir con el equipo.

## 1. Qué hicimos hoy

Contratamos un servidor en DonWeb y publicamos el sistema con su base de datos y archivos de demostración. Configuramos HTTPS, las tareas programadas y las copias de seguridad. Actualizamos el webhook de Meta y comprobamos que enviar `MENU` desde WhatsApp devuelve correctamente las opciones usando el servidor nuevo.

El sistema ya puede funcionar con nuestras computadoras apagadas. El entorno sigue destinado a **demostraciones de la facultad**, con datos y operaciones de prueba.

## 2. Dónde está alojado

| Concepto | Configuración |
| --- | --- |
| Proveedor | DonWeb Cloud Server, un servidor virtual (VPS) |
| Recursos | 2 vCPU, 4 GB de RAM y 30 GB de almacenamiento SSD contratados |
| Transferencia | 1 TB mensual |
| Sistema operativo | Ubuntu Server 24.04 LTS, instalación mínima de 64 bits |
| Respaldo contratado | Premium Diario de DonWeb |
| Importe contratado | ARS 20.382 por el primer mes, IVA incluido |
| Vencimiento mostrado en el panel | 18/11/2026 |
| Dirección del sistema | https://vps-6524456-x.dattaweb.com |

El importe corresponde a la contratación realizada. **Hay que consultar el precio de renovación en DonWeb antes del vencimiento**: no asumimos que la promoción se mantenga. Los servicios externos que eventualmente usemos, como IA u OCR, pueden tener costos adicionales.

Elegimos un VPS para alojar juntos la aplicación, la base de datos y los archivos, con control sobre las tareas automáticas. La configuración es para una demostración de baja concurrencia; todavía no hicimos una prueba de carga que certifique su capacidad para clientes reales.

Ubuntu es una distribución de Linux. Elegimos esta versión LTS por su estabilidad, documentación y soporte prolongado. No agrega una licencia de Windows Server. Podemos seguir desarrollando en Windows.

## 3. Tecnologías que usamos

| Parte | Tecnología y función |
| --- | --- |
| Aplicación | Laravel 13.30.1 sobre PHP 8.5: lógica, validaciones, permisos y páginas |
| Servidor web | Nginx recibe las conexiones y PHP-FPM ejecuta la aplicación |
| Base de datos | MariaDB 10.11 en el servidor, accesible únicamente desde el propio servidor |
| Interfaz | Blade, Tailwind CSS y recursos compilados con Vite |
| Node.js | Se utiliza para compilar la interfaz; no necesita permanecer ejecutándose para servirla |
| WhatsApp | Meta WhatsApp Cloud API, con el número de prueba y destinatarios permitidos |
| HTTPS | Certificado gratuito de Let's Encrypt, con renovación automática configurada |
| Automatización | Temporizadores de systemd ejecutan el programador de Laravel y los respaldos |
| Archivos | Almacenamiento persistente dentro del VPS; los comprobantes se conservan entre despliegues |

Las integraciones de IA son externas y opcionales. El servidor no ejecuta un modelo de IA propio. Desde el 20/10, el OCR de comprobantes usa Tesseract 5.3.4 instalado en el VPS, con español e inglés. Los PDF se convierten con Poppler y se lee su primera página. Esta lectura no requiere Google Cloud, claves externas ni cobros por imagen; consume recursos del hosting contratado.

## 4. Cómo acceder y probar

**Enlace para compartir:** https://vps-6524456-x.dattaweb.com/iniciar-sesion

Cualquier persona puede abrir el formulario de acceso, pero debe autenticarse para usar el panel. Se solicitaron dos cuentas personales para las pruebas: **`admin uli`** y **`admin agus`**, ambas con perfil de administrador total, gestión de usuarios y configuración de planes. Las contraseñas se entregan por separado y no se incluyen en este documento.

Un administrador total puede modificar los datos de demostración. Conviene que cada integrante use su propia cuenta para identificar sus intervenciones.

El panel se puede probar desde otro dispositivo con credenciales. Desde el 19/10, cualquier teléfono puede escribir al número propio terminado en 1032 e interactuar con el bot; ya no hace falta incorporarlo como destinatario de prueba de Meta. Si no se reconoce al cliente, el bot solicita su DNI u ofrece registrarse.

## 5. Qué está funcional

- Inicio y cierre de sesión, perfiles y permisos básicos; creación de usuarios desde el panel.
- Gestión de clientes, planes y servicios; consulta de cuenta corriente, cuotas y pagos.
- Registro de un cliente y contratación de un plan mediante el flujo de WhatsApp.
- Menú de WhatsApp para consultar cuenta, registrar reclamos, enviar comprobantes o solicitar un operador.
- Registro e historial de conversaciones; atención humana desde el panel, envío de respuestas y cierre de la conversación.
- Derivación a un operador después de dos mensajes que el bot no logra interpretar.
- Tickets con asignación de responsable, notas internas y cambios de estado hasta su cierre.
- Pantallas de comprobantes y conciliación ya disponibles, con funcionalidades pendientes de completar.
- Resumen administrativo básico.

Durante las pruebas se crearon el servicio **#7**, el ticket **#2** y la cuota ficticia **#24** por $18.000. Se comprobó la recepción de un recordatorio de prueba. **Estos importes no representan cobros reales.** Los datos se trasladaron al servidor, junto con el historial existente.

## 6. WhatsApp después del hosting

El webhook permanente quedó configurado en:

```text
https://vps-6524456-x.dattaweb.com/webhooks/whatsapp
```

Se verificó la URL en Meta y el usuario confirmó que el menú respondió correctamente después del cambio. Ya no dependemos del túnel temporal de Cloudflare ni de mantener encendida la PC local.

**Decisión posterior del 19/10:** se deshabilitó y detuvo el temporizador `villafane-scheduler.timer` para limitar los recordatorios con plantilla a pruebas puntuales autorizadas. El código conserva el horario de las **09:00 de Argentina**, pero actualmente no se ejecuta automáticamente en el servidor. Las respuestas a mensajes entrantes y los respaldos diarios siguen activos. El programador local también quedó detenido; no conviene volver a activarlo contra otra copia de los mismos datos durante estas pruebas.

El hosting no publica automáticamente la aplicación de Meta ni convierte el número de prueba en un canal para clientes reales.

**Migración del 19/10 al número propio:** después de que el usuario eliminara la cuenta de WhatsApp Business dedicada a las pruebas, Meta verificó y registró su línea terminada en 1032. El servidor se configuró con el nuevo identificador de teléfono `1372527232611726`, y se vació la lista de destinatarios para permitir respuestas a quienes escriban a ese número. Los recordatorios automáticos permanecen desactivados y no se agregó un método de pago. Se confirmó la recepción por webhook y se resolvieron los permisos del usuario del sistema tanto sobre el número como sobre la cuenta de mensajes vinculada. El usuario confirmó respuestas correctas y probó varios mensajes desde otro teléfono. Los recordatorios anteriores se probaron con el número de prueba de Meta; su habilitación con el número propio queda para más adelante.

## 7. Protección y respaldos

- HTTPS activo para el acceso al sistema y al webhook.
- Entorno de producción, depuración desactivada y sesiones con cookies seguras.
- Firewall habilitado; MariaDB escucha solo en la dirección local del servidor.
- Credenciales fuera de los archivos públicos y del paquete que se publica.
- Respaldo propio diario a las **03:00 de Argentina**, con base de datos, configuración privada y archivos persistentes; limpieza de copias antiguas después de siete días.
- Respaldo propio adicional antes de cada despliegue, más el servicio Premium Diario contratado en DonWeb.

Se comprobó que el respaldo propio se genera y contiene sus archivos. La restauración completa de una copia todavía no fue ensayada. Las copias propias están en el mismo VPS; conviene incorporar también una copia externa para mayor independencia.

## 8. Cómo seguiremos desarrollando

Seguimos trabajando en el repositorio local. Los cambios se guardan en Git y se pueden compartir mediante push como hasta ahora.

**Un push todavía no actualiza automáticamente el servidor.** Por ahora, el despliegue consiste en compilar la interfaz, preparar un paquete, transferirlo por SSH y ejecutar el script de publicación. El script instala las dependencias, hace un respaldo, aplica migraciones y comprueba la respuesta HTTPS.

Cada publicación usa una carpeta de versión nueva. La configuración, la base de datos y los archivos persistentes se conservan por separado. Si falla la comprobación final, el script intenta volver a la versión anterior del código cuando existe; **las migraciones de base de datos no se revierten automáticamente**.

Podemos automatizar el despliegue desde Git más adelante. Los cambios de estructura de la base deben hacerse mediante migraciones, sin reemplazar la base del servidor por la copia local.

## 9. Qué falta

### Actualización del módulo 2 — 19/10/2026

Las dos comprobaciones manuales pendientes se completaron juntas: a las **01:32 de Argentina**, el temporizador habitual del servidor ejecutó una tarea temporal de Laravel y envió un único aviso de cuota vencida. El resultado fue **1 enviada, 0 omitidas y 0 fallidas**. Meta informó entrega y el usuario confirmó la recepción en WhatsApp.

Para la prueba autorizada se cambió temporalmente el vencimiento de la cuota ficticia #24 al 18/10. Al terminar se restauraron su vencimiento original **19/10/2026** y su estado **pendiente**. No se registró ningún pago. El aviso aclara que es una demostración y que no se debe pagar.

Se retiró la tarea temporal y se comprobó que el código de programación recuperó su contenido original. En ese momento quedó únicamente el recordatorio habitual de las **09:00 de Argentina**. Se comprobó el envío automático mediante la prueba de la 01:32; una ejecución exactamente a las 09:00 no fue observada. Posteriormente se desactivó el temporizador por decisión del usuario para limitar los avisos a pruebas puntuales, como se detalla en la sección 6.

La cuota #24 tiene registrados tanto el aviso previo al vencimiento como el aviso de vencida. Repetir esos avisos para esa cuota debe omitirse para evitar duplicados; no debe esperarse otro mensaje de ella solo por llegar las 09:00.

La revisión automatizada final del 19/10 pasó con **13 pruebas y 43 aserciones** del módulo 2, usando SQLite en memoria y servicios externos simulados.

**Módulo 2 cerrado para la demostración el 19/10, por decisión del usuario**, con el bot funcionando en el número propio. Los recordatorios automáticos quedan desactivados; su incorporación al canal propio, junto con la revisión de plantillas y facturación aplicable, se retomará más adelante. Las funciones pendientes de otros módulos se detallan debajo. Los cambios de este documento deben guardarse en Git; esta actualización no realiza commit ni push.

### Pendientes del sistema completo

- **Módulo 1:** automatización de la generación mensual de cuotas.
- **Módulo 3:** circuito de demostración verificado con recepción, OCR, aprobación, rechazo, duplicados, avisos por WhatsApp y aprobación manual tras una lectura fallida. Los bloqueos de importes parciales/sobrantes y operaciones repetidas se verificaron con pruebas automatizadas. Se mantienen las limitaciones documentadas: cuotas completas, revisión humana, lectura de la primera página del PDF y avisos dentro de las 24 horas.
- **Módulo 4:** completar funciones previstas de soporte, como creación manual de tickets y asignación automática por disponibilidad.
- **Módulo 5:** reportes y exportaciones PDF/Excel; hoy hay un resumen básico.
- **Módulo 6:** completar edición de usuarios y cambio de contraseña desde la interfaz.

Antes de incorporar clientes reales también harán falta pruebas de carga, restauración de respaldos, revisión de permisos y configuración definitiva de los servicios externos.

### Actualización del módulo 3 — 20/10/2026

Se publicó la versión `20261020T044008Z-46879`, con almacenamiento privado de comprobantes y descarga autenticada. Se trasladaron los archivos históricos públicos verificando su hash y se bloqueó su antigua ruta pública en Nginx. El registro de pagos exige cancelar cuotas completas en orden de antigüedad: por decisión del usuario, todavía no se admiten pagos parciales ni saldos a favor. También se incorporaron controles de operaciones duplicadas y bloqueo transaccional al aprobar o rechazar.

El servicio `villafane-ocr.service` procesa una tarea por vez, con un límite de memoria de 512 MB, CPU de 75% y tiempos máximos por archivo. No depende del temporizador de recordatorios. Una falla de lectura conserva el original y permite la revisión manual. El OCR propone datos; no valida que el dinero haya ingresado a una cuenta bancaria.

La suite local inicial pasó con **62 pruebas y 263 aserciones**. En el VPS se extrajeron correctamente operación, monto y fecha de un PDF ficticio y su imagen. La prueba de cola creó el comprobante **#11**, quedó con OCR procesado, validación pendiente y sin pago asociado; no se enviaron mensajes. El servicio de OCR está activo y habilitado al iniciar el servidor.

Luego se publicó la corrección del bot `20261020T045924Z-48351`: los saludos y agradecimientos no acumulan errores, los intentos fallidos se reinician tras 30 minutos de inactividad y MENU permite retomar una derivación si todavía no fue tomada por un operador. La suite completa pasó con **68 pruebas y 293 aserciones**.

Pruebas integrales confirmadas por el usuario y verificadas en el servidor:

- El comprobante **#12**, enviado como imagen por WhatsApp, recuperó operación **8765432109**, importe **$18.000** y fecha **20/10/2026**. Su aprobación generó un único **pago ficticio #7** y canceló la **cuota #25**, período noviembre y vencimiento **02/11/2026**, del servicio de demostración #8.
- Reenviar el mismo archivo identificó el comprobante #12 y no creó otro registro ni pago.
- El comprobante **#13**, recibido como PDF con operación **7654321098**, fue leído y rechazado con motivo registrado; no generó pagos.
- Se habilitó `COMPROBANTES_NOTIFICAR_RESULTADO=true` en el host. Se enviaron los avisos de aprobación y rechazo de estas pruebas y el usuario confirmó ambos en WhatsApp. Los mensajes aclaran que se trata de una demostración y que no se debe pagar. Solo se envían dentro de las 24 horas desde el último mensaje del cliente; fuera de esa ventana queda pendiente el aviso y no se envían plantillas.
- La revisión automatizada más reciente de M3 pasó con **20 pruebas y 111 aserciones**: incluye importes parciales/sobrantes, operación repetida con otro archivo y lectura fallida sin inventar datos, conservando el original y permitiendo conciliación manual.

- La última prueba integral utilizó el comprobante **#14**, una imagen sin datos legibles. El OCR falló, conservó el original y dejó monto y fecha sin detectar. El usuario completó la operación **6543210987**, el importe **$18.000** y la fecha **20/10/2026** desde el panel. La aprobación generó un único **pago ficticio #8**, canceló la **cuota #26** de diciembre, con vencimiento **02/12/2026**, y envió el aviso automáticamente. El usuario mostró su recepción y el servidor registró el aviso como leído. Los valores manuales de monto y fecha quedaron en el pago; los campos detectados por OCR permanecieron vacíos para conservar esa distinción.

Con esta última comprobación se completaron las pruebas principales previstas para M3 en la demostración. Todas las cuotas, comprobantes y pagos de estas comprobaciones son ficticios. Los recordatorios automáticos continúan desactivados. Esta actualización no realiza commit ni push.

### Ampliación de la demostración: transferencias reales de $5 y plantillas — 20/10/2026

Por pedido del usuario, se publicó `20261020T054429Z-50280`. Se agregó un plan exclusivo **#4** de $5, el servicio de prueba **#10** para el cliente que venía probando y la cuota **#27**, con vencimiento **23/10/2026**. Se registró la cuenta receptora **#3**, de Mercado Pago, con el alias y titular aportados por el usuario. Los servicios y cuotas anteriores mantienen sus importes. Al preparar esta actualización, la cuota de $5 continúa pendiente y no tiene ningún pago registrado.

El comando **PAGAR** y la cuenta corriente muestran las instrucciones solo al cliente con una cuota pendiente de $5 del plan configurado. Se aclara que se transfiere dinero real, se debe comprobar el titular y enviar el comprobante, y la aprobación depende de verificar el ingreso manualmente en Mercado Pago. No se incorporó acceso a la API de movimientos ni acreditación automática. Esta modalidad no requiere credenciales de Mercado Pago; recibir por alias no equivale a una integración API de Checkout.

La suite completa pasó con **73 pruebas y 324 aserciones**. Se probaron la disponibilidad de instrucciones para el cliente habilitado, su ausencia para otros clientes o cuotas ya pagadas, la lista separada de destinatarios de recordatorios y el envío de plantillas fuera de ventana sin duplicar avisos. El OCR con un comprobante descargado de Mercado Pago sigue pendiente: se utilizará el de la primera transferencia real, ocultando datos sensibles para compartirlo en esta conversación.

Se presentaron en Meta cuatro plantillas `UTILITY`, idioma `es_AR`: `demo_cuota_proxima_v1`, `demo_cuota_vencida_v1`, `demo_pago_aprobado_v1` y `demo_pago_rechazado_v1`. La consulta posterior confirmó las cuatro **PENDING**. El código permite enviar el resultado fuera de las 24 horas si se habilita expresamente y se configura la plantilla; dentro de la ventana conserva el texto libre. **El envío fuera de ventana permanece desactivado** hasta resolver aprobación y configuración de facturación de Meta con el usuario.

Los recordatorios tienen ahora una habilitación explícita y una lista propia de destinatarios. La lista vacía bloquea los recordatorios reales y no restringe las respuestas del bot a mensajes entrantes. Se configuró únicamente el teléfono del usuario para las futuras pruebas; tanto la habilitación de recordatorios como el temporizador permanecen desactivados hasta tener las plantillas y la facturación listas. Queda pendiente observar una ejecución programada en el número propio.

### Cuenta de la empresa para todos los clientes — 20/10/2026

El usuario aclaró que su Mercado Pago debe representar a la cuenta de la empresa para **todos los clientes**, y pidió usar precios bajos en los planes de megas existentes. Esta configuración reemplaza la limitación anterior a un cliente y plan de $5. Se publicó `20261020T055843Z-51090`.

- **Hogar 20, 20 Mbps: $5**; **Hogar Plus 50, 50 Mbps: $10**; **Comercio 100, 100 Mbps: $15**. Los tres permanecen activos. Se actualizaron también 20 cuotas pendientes de esos planes para poder probar transferencias pequeñas y respetar la cancelación de cuotas completas. Los pagos históricos y sus importes se conservaron.
- La cuenta receptora #3 ahora tiene como nombre de titular **Serrano Ulises Ivan**, alias **uliserrano.mp**, y es la única cuenta activa. Las dos cuentas ficticias anteriores quedaron inactivas para impedir seleccionarlas al acreditar nuevos pagos; siguen disponibles como referencias del historial.
- El plan separado #4 quedó inactivo y su servicio #10 se dio de baja. La cuota pendiente #27 de $5 se trasladó al servicio Hogar 20 #8 del mismo cliente, sin duplicarla. Se conservó su vencimiento del 23/10 para la prueba que ya estaba preparada.
- El comando **PAGAR** y la cuenta corriente muestran el mismo destino a cualquier cliente identificado. El importe indicado corresponde a su cuota pendiente más antigua. Si no tiene cuotas pendientes se muestran los datos de la empresa aclarando que no necesita transferir. Se quitó la condición de pertenecer a un plan especial o adeudar exactamente $5.
- El panel ofrece la cuenta de la empresa para acreditar, y el servicio de facturación bloquea registrar pagos en otra cuenta cuando está habilitado este destino único. La cuenta receptora se configura con `TRANSFERENCIAS_HABILITADAS=true` y `TRANSFERENCIAS_ID_CUENTA=3`; se retiraron las variables de la configuración limitada anterior.
- **74 pruebas y 337 aserciones** pasaron. Incluyen clientes con distintos planes, un cliente sin deuda, cuenta receptora inactiva y rechazo de otra cuenta aunque esté activa, tanto en la aprobación del comprobante como en el registro manual de pago.

Las transferencias son dinero real recibido en Mercado Pago. El sistema continúa requiriendo verificación manual del ingreso por el personal de cobranza antes de aprobar; no consulta movimientos ni acredita automáticamente. Los avisos por plantilla y los recordatorios siguen con el estado pendiente de configuración ya indicado. No se realizó commit ni push.

### Ajuste de importes y texto de PAGAR — 20/10/2026

Por pedido posterior del usuario, los precios bajos se reemplazaron por **$100 para Hogar 20 Mbps, $150 para Hogar Plus 50 Mbps y $200 para Comercio 100 Mbps**. Se actualizaron las 21 cuotas pendientes de esos planes y el catálogo inicial del repositorio; la cuota #27 ahora es de **$100**. Los pagos ya registrados conservaron sus importes históricos.

Se publicó `20261020T061235Z-51991`. El mensaje de PAGAR ahora comienza con «Transferencia a la cuenta de la empresa (Pruebas académicas).» y termina indicando que el personal verificará el ingreso en Mercado Pago y aprobará manualmente. A pedido del usuario, se retiró del texto la aclaración técnica sobre la consulta de movimientos. Esto solo modifica la redacción: las transferencias continúan siendo reales y la verificación sigue siendo manual. El alias común sigue siendo `uliserrano.mp`, titular Serrano Ulises Ivan.

Se verificaron **74 pruebas y 337 aserciones**, respaldo previo y publicación correcta en el host. No se realizó commit ni push.

### Lectura de comprobante ARQ/DolarApp — 20/10/2026

La primera prueba con un comprobante de ARQ creó el registro #15 y conservó la imagen, pero no extrajo los campos: el formato usa un ID de Coelsa alfanumérico, la etiqueta «Monto enviado» y un mes escrito en letras. Se amplió la extracción para admitir esos formatos sin tomar CUIT o CBU/CVU como identificador de operación. Se agregó una prueba con texto sintético sin datos personales; las **24 pruebas de M3 y 136 aserciones** pasaron.

Se publicó `20261020T061846Z-52708` y se reprocesó el mismo registro #15 mediante la cola. Ahora se recuperaron importe **$100**, fecha **20/10/2026** e ID alfanumérico. El comprobante conserva validación **pendiente**, sin pago asociado. La captura original muestra una transacción **PENDIENTE**: el administrador debe comprobar el ingreso en Mercado Pago antes de aprobar. Tesseract confundió algunos caracteres del ID; ese campo debe cotejarse con el original, preferentemente con el PDF descargado de ARQ. Recuperar los tres campos no implica que sus caracteres sean exactos ni que el dinero se haya acreditado.

**Resultado posterior confirmado:** el usuario informó que los **$100 ingresaron en su Mercado Pago** y aprobó el comprobante desde el panel. Se verificó en el servidor que el comprobante #15 quedó aprobado, generó un único **pago #9 por $100** a la cuenta `uliserrano.mp` y canceló la **cuota #27** del servicio #8. El aviso automático quedó registrado como **leído**, y el usuario mostró su recepción en WhatsApp. Quedó completado el recorrido con transferencia real desde ARQ, recepción del comprobante, extracción asistida, comprobación manual del ingreso, aprobación, cancelación de cuota y aviso. Esta prueba no incorpora consulta automática de movimientos de Mercado Pago ni elimina la necesidad de revisar los caracteres del identificador extraído por OCR.

### Preparación de la habilitación de recordatorios y plantillas — 20/10/2026

El usuario pidió retomar las pruebas pendientes de M2. La consulta de Meta confirmó que las cuatro plantillas propias siguen en estado **PENDING**, categoría `UTILITY`, idioma `es_AR`; solo la plantilla de ejemplo `hello_world` está aprobada. No se enviaron mensajes de prueba con plantillas pendientes.

Se habilitó y activó `villafane-scheduler.timer` para ejecutar el programador cada minuto, también al iniciar el VPS. Laravel mantiene el recordatorio diario a las **09:00 de Argentina**. Esto reemplaza el estado anterior del temporizador deshabilitado. **Los envíos de recordatorios siguen bloqueados** por `WHATSAPP_RECORDATORIOS_HABILITADOS=false`, y `COMPROBANTES_PLANTILLAS_FUERA_VENTANA=false` conserva pendientes los avisos fuera de ventana. La lista exclusiva de destinatarios de recordatorios contiene únicamente el teléfono del usuario.

Se intentó consultar si había financiamiento asociado a la cuenta, pero Meta rechazó la consulta con el permiso disponible (código 10: requiere que el negocio sea proveedor de soluciones de WhatsApp). Eso no confirma ni descarta un método de pago. Se pidió al usuario revisar el apartado de facturación desde la interfaz de Meta. Quedan pendientes aprobación de las plantillas, verificación de facturación, habilitación de los envíos y prueba de entrega programada en el número propio. No se alteraron fechas del historial ni se simuló que hubiera transcurrido la ventana de 24 horas.

### Control de consumo para la demostración — 20/10/2026

El usuario priorizó conservar los requisitos funcionales con el mínimo uso real cobrable. Se publicó `20261020T064614Z-54156` con una tabla persistente de consumo por número emisor y mes UTC, reserva transaccional previa a cada envío y límites en el servicio común de WhatsApp, de modo que se aplican al bot, operadores y notificaciones.

- **Respuestas normales: máximo 900 intentos por mes.** El límite deja margen respecto del cupo de 1.000 mensajes de servicio que Meta informa para el número. El historial real previo del mes se incluye conservadoramente; los identificadores de simulación se excluyen. En la comprobación del despliegue, el contador local mostró **84 / 900**.
- **Plantillas reales de prueba: máximo 4 intentos por mes y 2 por día UTC**, una vez habilitadas. Se admiten únicamente las cuatro plantillas configuradas y el teléfono de prueba del usuario. La lista de plantillas es independiente de la lista de destinatarios de respuestas normales, que sigue abierta a clientes entrantes.
- **Actualmente continúan bloqueadas las plantillas reales**, los recordatorios y los avisos fuera de ventana. La consulta final confirmó que las cuatro plantillas propias siguen `PENDING`. El programador está activo, pero no habilita envíos por sí solo. Falta aprobar las plantillas y revisar la facturación antes de efectuar las pruebas reales limitadas.
- El contador se muestra en **Conversaciones**. Es un control local de intentos, no una factura de Meta ni una medición del uso en otras aplicaciones. Las reservas se conservan también ante errores o tiempos de espera de red, para evitar reintentar sin presupuesto cuando Meta pudo haber recibido la solicitud. Las pruebas en modo simulación no contactan a Meta ni consumen los cupos.
- Si se alcanza un límite, se bloquea el envío antes de realizar la solicitud HTTP. El pago y la decisión del comprobante conservan su resultado, y el aviso fallido puede revisarse por separado. No se promete gasto cero para los envíos reales con plantillas; la tarifa efectiva se confirma en Meta.

Pasaron **84 pruebas y 376 aserciones**, incluidas pruebas de topes diario/mensual, cambio de mes, historial previo, simulación, fallo de red, destinatarios y plantillas ajenos a la demo, vista del contador y aprobación de pago con aviso bloqueado. En el host se verificaron migración, bloqueo sin envío, servicio OCR, programador y salud HTTP 200. No se enviaron mensajes reales para comprobar estos límites y no se realizó commit ni push.

## 10. Comprobaciones realizadas al publicar

- Aplicación accesible por HTTPS: inicio de sesión y página de salud con respuesta correcta.
- Recursos compilados de la interfaz disponibles.
- Verificación del webhook correcta y respuesta de WhatsApp confirmada por el usuario.
- API de clientes rechaza consultas sin autenticación; el archivo de configuración privado no es accesible por web.
- Base trasladada con 10 clientes, 7 servicios, 10 conversaciones, 2 tickets y 24 cuotas al momento de la migración.
- Nginx, PHP-FPM, MariaDB, renovación del certificado y temporizadores configurados para iniciar automáticamente.
- Antes del despliegue, la suite automatizada había pasado con **43 pruebas y 167 aserciones**. Eso no reemplaza las comprobaciones manuales que siguen pendientes.

## Integración de M2, M3 y M4 y revisión visual — 20/10/2026

Se publicó la rama `Integracion-m2-m3-m4`, con los ajustes de la revisión, en la versión **`20261020T074959Z-56728`**. La integración incorpora las pantallas y funciones de tickets del módulo 4, conserva el circuito de WhatsApp y pagos, y resuelve los cruces de rutas, permisos y notas internas.

- Se agregó una migración que permite tickets manuales sin conversación, sin recrear la tabla ni modificar los registros existentes. Se comprobó su ejecución en MariaDB del host.
- Se revisaron en navegador las pantallas de resumen, clientes, servicios, planes, cobranza, conversaciones, comprobantes, listado y detalle de tickets, y formulario de ticket manual. Se comprobaron modo claro, modo oscuro y la adaptación de tickets y su formulario a una pantalla de 390 píxeles. El filtro de estado de tickets también se verificó en el servidor.
- Se corrigieron contraste de textos y botones, distribución de filtros y encabezados, controles de estado que aparecían antes de asignar un ticket y ajuste de formularios móviles. Se incorporaron los filtros de fecha visibles y su validación. Los importes no leídos por OCR ahora se muestran como «Sin detectar» en la bandeja; el mensaje de cuenta sin cuotas pendientes ya no queda dentro de una tabla desplazable.
- **Verificación automatizada: 98 pruebas aprobadas, 451 aserciones**, formato PHP correcto y compilación de recursos correcta. Incluye pruebas de permisos, ciclo de vida de tickets, fechas, carga manual y avisos limitados por ventana y consumo. La revisión visual del host fue de consulta: no se aprobaron pagos ni se modificaron tickets para probar pantallas.
- Se compararon las cantidades de registros antes y después: **12 clientes, 10 servicios, 3 tickets, 1 nota interna, 13 comprobantes, 7 pagos, 27 cuotas y 199 mensajes**. Se conservó el Pago #9 de $100 en la cuenta receptora #3. No se ejecutaron seeders de demostración en el host.
- Se confirmó salud HTTP **200**, acceso no autenticado a tickets y archivos privados con redirección al ingreso (**302**), y bloqueo de la ruta pública de comprobantes (**404**). PHP-FPM, OCR y programador permanecen activos; la última ejecución del programador informa resultado correcto.
- El respaldo privado final es **`/var/backups/villafane-wifi/respaldo-20261020T075001Z.tar.gz`**; se comprobó que contiene el respaldo de base de datos y archivos. Se generaron respaldos antes de cada publicación.

**Configuración conservada:** control de consumo activo, respuestas con límite 900/mes, plantillas con límites 4/mes y 2/día. Los recordatorios reales y las plantillas cobrables siguen desactivados. El usuario ya agregó el método de pago en Meta; el último estado conocido de las cuatro plantillas es «En revisión». Faltan las pruebas reales de recordatorios y avisos fuera de las 24 horas.

Los avisos de novedades de tickets se integraron al envío real y al historial, con deduplicación y control de ventana y consumo; no incluyen notas técnicas internas. **Quedan desactivados por defecto** mediante `TICKETS_NOTIFICAR_RESULTADO=false`. Si se habilitan, solo pueden enviar dentro de las 24 horas desde el mensaje del cliente: no se configuró una plantilla fuera de ventana para tickets. La gestión de tickets no depende de esos avisos. No se enviaron mensajes reales ni se generaron cobros durante esta revisión.

Los ajustes posteriores a la integración quedaron como cambios locales pendientes de commit y push. La publicación del host fue manual; publicar una rama en GitHub no despliega automáticamente.
