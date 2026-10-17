# Implementación del Módulo 2 — Atención por WhatsApp

## Alcance implementado

| Requerimiento | Implementación |
|---|---|
| RF-10 | Webhook público de WhatsApp con verificación, validación de firma, deduplicación, apertura o reutilización de conversaciones y respuesta automática. |
| RF-11 | Clasificación de intención con OpenAI Responses API, salida estructurada y clasificador local de respaldo. Solicita reformulación y escala después del segundo intento fallido. |
| RF-12 | Identificación por teléfono o documento. Para personas no registradas permite alta conversacional, selección de plan y contratación del servicio. |
| RF-13 | Consulta del estado de cuenta, total pendiente y próximo vencimiento mediante `ServicioCuentaCorriente`. |
| RF-14 | Registro de reclamo y creación de ticket asociado a la conversación y al servicio del cliente. |
| RF-15 | Escalado a atención humana, toma exclusiva de la conversación y desactivación del bot mientras interviene un usuario interno. |
| RF-16 | Historial completo de mensajes entrantes, del bot, del sistema y de usuarios internos; incluye estados enviado, entregado, leído y fallido. |
| RF-17 | Cierre automático por el bot luego de resolver cuenta, reclamo, contratación o comprobante; cierre manual por el responsable o un administrador después del escalado. |
| RF-18 | Comando diario para notificar cuotas próximas a vencer o vencidas mediante plantilla de WhatsApp, con idempotencia y reintento de fallos. |
| RF-19 | Recepción de JPG, PNG o PDF, descarga desde Meta, límite de tamaño, almacenamiento privado, hash SHA-256 y asociación con cliente, conversación y mensaje. |

## Estructura principal

- `conversacion` conserva el estado, modo de atención, usuario responsable, intención, paso y contexto del flujo.
- `mensaje` registra el historial y usa los identificadores externos de Meta para deduplicar eventos.
- `ticket` conserva los reclamos técnicos y su asignación.
- `comprobante` usa la estructura base del Módulo 3 y una migración adicional agrega el contexto de WhatsApp. La recepción del Módulo 2 deja el registro pendiente; OCR y conciliación continúan en el Módulo 3.
- El panel web se encuentra en `/conversaciones`: la lectura requiere `consultar_conversaciones` y las acciones requieren `gestionar_conversaciones`. Incluye búsqueda por teléfono, documento o cliente y filtro por todos los estados.

## Configuración requerida

Completar en `.env`:

```dotenv
WHATSAPP_VERIFY_TOKEN=
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_APP_SECRET=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_MODO_SIMULACION=false
WHATSAPP_GRAPH_API_VERSION=v25.0
WHATSAPP_VALIDAR_FIRMA=true
WHATSAPP_MEDIA_DISK=local
WHATSAPP_MEDIA_MAX_KB=10240
WHATSAPP_TEMPLATE_VENCIMIENTO=recordatorio_vencimiento
WHATSAPP_TEMPLATE_LANGUAGE=es_AR

OPENAI_API_KEY=
OPENAI_MODEL=
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_TIMEOUT=15
IA_CONFIANZA_MINIMA=0.65
```

Para trabajar sin credenciales externas puede configurarse `WHATSAPP_MODO_SIMULACION=true`. Esto habilita `/simulador-whatsapp`, genera mensajes y archivos de prueba locales y mantiene desactivadas las llamadas a Meta. Si `OPENAI_API_KEY` y `OPENAI_MODEL` están vacíos, el sistema usa el clasificador local y el menú numérico 1–4.

La plantilla `recordatorio_vencimiento` debe estar aprobada en WhatsApp Manager y contener tres parámetros de cuerpo, en este orden: nombre del cliente, fecha de vencimiento e importe.

## Puesta en marcha

1. Instalar las dependencias PHP del proyecto.
2. Completar las variables de entorno.
3. Ejecutar las migraciones de Laravel.
4. Configurar en Meta el webhook `GET/POST /webhooks/whatsapp` y suscribir el campo `messages`.
5. Mantener activo `php artisan schedule:run` desde el programador del servidor para que el aviso diario de las 09:00 se ejecute.

El webhook valida `X-Hub-Signature-256`. La desactivación de esta verificación debe limitarse a un entorno local controlado.


## Verificación de coherencia

- El menú numérico 1–4 y el clasificador local permiten probar el flujo sin una cuenta paga de IA.
- La identificación por DNI vincula y actualiza el teléfono de WhatsApp del cliente.
- Los estados de entrega solo avanzan (`enviado` → `entregado` → `leído`) y no retroceden ante webhooks fuera de orden.
- Al tomar una conversación, el operador envía una presentación y queda como responsable exclusivo.
- El detalle de conversación muestra mensajes, tickets y comprobantes asociados.
- Pruebas automatizadas: `tests/Unit/OpcionMenuWhatsappTest.php` y `tests/Feature/Modulo2WhatsappTest.php`.
