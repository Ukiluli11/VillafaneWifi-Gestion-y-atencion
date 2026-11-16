# Aplicación Villafañe Wifi

Implementación vigente del sistema con Laravel 13, PHP, JavaScript y MariaDB.

## Organización

- `app/Models`: entidades del DER implementadas con Eloquent.
- `app/Dominio`: servicios que coordinan reglas y casos de uso.
- `app/Autorizacion`: políticas POO para administradores y empleados.
- `app/Http/Controllers/Web`: panel administrativo.
- `app/Http/Controllers/Api/V1`: API REST versionada.
- `database/migrations`: esquema reproducible de MariaDB.
- `resources/views`: pantallas Blade del panel.
- `tests`: pruebas funcionales y de dominio.

Los diagramas están en `../Diagramas/`; el informe y los manuales se encuentran
en `../Documentación/`.

## Cierre de los requerimientos de la demostración

**RF-12.** WhatsApp vincula una conversación cuando el número coincide de forma
inequívoca con el teléfono registrado. Si un cliente escribe desde otro número,
un operador verifica su identidad antes de actualizar el contacto. El DNI
identifica el registro durante esa verificación. Los números nuevos también
pueden iniciar un alta y contratar un plan desde el chat con el comando
«REGISTRARME». Este criterio actualiza la descripción de identificación por
teléfono o DNI del Informe del Sistema.

**RF-11.** La clasificación conversacional admite Gemini Flash-Lite y OpenAI.
El cliente puede escribir con sus palabras. El bot usa el tema de una
consulta reciente para interpretar una repregunta y pide una aclaración
cuando corresponda. Los datos de cuenta, pagos y reclamos los resuelve la
aplicación. El simulador y el respaldo ante fallos del proveedor usan reglas
locales. Después de dos intentos sin interpretar la consulta, el bot deriva
a un operador.

Para la demo, `tools/windows/publicar-demo-gemini-vps.ps1` publica el código
y configura Gemini para el número de prueba. El script permite indicar otros
números con `-TelefonosDemo` separados por coma. La clave se ingresa en la
terminal del VPS y el script valida el acceso con una consulta ficticia antes
de desplegar. Usar consultas ficticias durante las pruebas con el nivel gratuito.

### Verificación desde PowerShell

Ejecutar desde la raíz del repositorio.

| Comprobación | Comando | Evidencia |
| --- | --- | --- |
| Recordatorio automático vencido (RF-18 y RF-37) | `.\Codigo\tools\windows\verificar-requisitos-vps.ps1 -CuotaDemo 26` | Estado del temporizador, horario del programador y estado del mensaje asociado a la cuota. Hacer la comprobación tras la ejecución programada y contrastar con el WhatsApp recibido. |
| Clasificación real y tiempo de respuesta (RF-11 y RNF-04) | `.\Codigo\tools\windows\verificar-requisitos-vps.ps1 -ProbarIA` | Comprueba si la IA está configurada. Si lo está, envía al proveedor una consulta ficticia y mide la clasificación. Para medir el recorrido completo, tomar además el tiempo entre el envío y la respuesta en el chat de prueba. |
| Salud HTTPS durante 24 horas (RNF-03) | `.\Codigo\tools\windows\verificar-requisitos-vps.ps1 -Muestras 289 -IntervaloSegundos 300` | Guarda en un CSV las respuestas y tiempos de 289 comprobaciones desde el equipo de prueba. Mantener PowerShell abierto mientras dure la medición. |
| Carga con MariaDB (RNF-07) | `.\Codigo\tools\windows\probar-escalabilidad-mariadb.ps1` | Usa una instancia local temporal y muestra las medianas, los máximos y las consultas SQL de ocho pantallas con 100 y 800 clientes ficticios. |

La comprobación de recordatorios consulta el registro existente. El parámetro
`-ProbarIA` habilita una sola solicitud real al proveedor con texto ficticio
y puede consumir un cargo de la API. La medición HTTPS comprueba la salud
pública del VPS. La recepción de mensajes se verifica por separado con un
WhatsApp de prueba. El ensayo de escala necesita los binarios de MariaDB en
`PATH` y la extensión `pdo_mysql` de PHP.
