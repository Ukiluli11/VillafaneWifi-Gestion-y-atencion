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

Las integraciones de IA son externas y opcionales. El servidor no ejecuta un modelo de IA propio. El OCR real de imágenes es un pendiente del módulo de comprobantes.

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
- **Módulo 3:** completar comprobantes y conciliación, especialmente OCR real de imágenes y los flujos pendientes.
- **Módulo 4:** completar funciones previstas de soporte, como creación manual de tickets y asignación automática por disponibilidad.
- **Módulo 5:** reportes y exportaciones PDF/Excel; hoy hay un resumen básico.
- **Módulo 6:** completar edición de usuarios y cambio de contraseña desde la interfaz.

Antes de incorporar clientes reales también harán falta pruebas de carga, restauración de respaldos, revisión de permisos y configuración definitiva de los servicios externos.

## 10. Comprobaciones realizadas al publicar

- Aplicación accesible por HTTPS: inicio de sesión y página de salud con respuesta correcta.
- Recursos compilados de la interfaz disponibles.
- Verificación del webhook correcta y respuesta de WhatsApp confirmada por el usuario.
- API de clientes rechaza consultas sin autenticación; el archivo de configuración privado no es accesible por web.
- Base trasladada con 10 clientes, 7 servicios, 10 conversaciones, 2 tickets y 24 cuotas al momento de la migración.
- Nginx, PHP-FPM, MariaDB, renovación del certificado y temporizadores configurados para iniciar automáticamente.
- Antes del despliegue, la suite automatizada había pasado con **43 pruebas y 167 aserciones**. Eso no reemplaza las comprobaciones manuales que siguen pendientes.
