# Módulo 2 — Atención por WhatsApp

Estado al 20/10/2026. Proyecto de demostración académica. Este resumen actualiza el estado descrito en el informe del 17/10; aquel documento se conserva como antecedente.

## Qué hicimos y cómo funciona

- Conectamos el número propio de WhatsApp con el sistema alojado en un servidor. El bot recibe los mensajes y responde sin depender de que la PC esté encendida.
- Incorporamos identificación del cliente, registro de nuevos clientes y contratación, consulta de cuenta, reclamos, envío de comprobantes y datos para transferir.
- Agregamos atención humana: la conversación puede derivarse a un operador, que la toma, responde desde el panel y cierra la atención. El bot no interrumpe una conversación tomada por un operador. Si la derivación todavía no fue tomada, el cliente puede volver al bot con «MENÚ».
- Desarrollamos recordatorios de cuota próxima a vencer y vencida, con prevención de duplicados. El programador del servidor está activo y la tarea está prevista para las 09:00, hora de Argentina.
- Incorporamos un contador de consumo en Conversaciones y límites para mantener bajo el uso de mensajes potencialmente cobrables.

## Qué verificamos

Probamos el bot desde WhatsApp. La última ejecución específica de M2 pasó **21 pruebas automáticas con 86 verificaciones**, incluyendo derivación, toma, respuesta y cierre por un operador, y protección frente a interrupciones del bot. Las pruebas automáticas no envían mensajes reales.

## Limitaciones y pendientes

- Las cuatro plantillas de recordatorios y resultados de comprobantes siguen **en revisión por Meta**. El método de pago ya fue agregado.
- Los recordatorios reales siguen desactivados, aunque el programador funciona. Falta habilitarlos de forma controlada y comprobar la recepción de una ejecución programada.
- Para enviar avisos fuera de las 24 horas desde el último mensaje del cliente necesitamos plantillas aprobadas. Falta comprobar un envío real fuera de esa ventana.
- Los envíos reales de plantillas siguen bloqueados. Cuando se habiliten para pruebas, estarán restringidos al número autorizado, con un máximo configurado de **2 al día y 4 al mes**. Las respuestas normales tienen un límite de **900 al mes**.
- Estos límites son controles del sistema, no un límite de facturación de Meta: contabilizan reservas de envío, incluso ante ciertos fallos, y no contemplan envíos hechos desde otras herramientas. No garantizan costo cero.

**Cierre pendiente:** aprobación de plantillas y pruebas reales controladas de recordatorios, ausencia de duplicados y avisos fuera de las 24 horas.
