# Diagramas del Módulo 2 Atención por WhatsApp

## Objetivo

Esta actualización documenta el comportamiento previsto para los requerimientos RF-10 a RF-19. El caso de uso muestra las funciones disponibles y sus actores, mientras que los diagramas de secuencia describen la colaboración temporal entre WhatsApp Cloud API, los controladores, los servicios del dominio y la base de datos.

## Archivos creados

| Diagrama | Fuente editable | Exportación |
|---|---|---|
| Caso de uso específico | `CU_Modulo2_AtencionWhatsApp.xml` | `CU_Modulo2_AtencionWhatsApp.png` |
| Recepción, identificación, interpretación y escalado | `Secuencia_Modulo2_Recepcion_Identificacion_Escalado.xml` | `Secuencia_Modulo2_Recepcion_Identificacion_Escalado.png` |
| Consulta de estado de cuenta | `Secuencia_Modulo2_Consulta_Estado_Cuenta.xml` | `Secuencia_Modulo2_Consulta_Estado_Cuenta.png` |
| Registro de reclamo y ticket | `Secuencia_Modulo2_Registro_Reclamo.xml` | `Secuencia_Modulo2_Registro_Reclamo.png` |
| Notificación de vencimientos | `Secuencia_Modulo2_Notificacion_Vencimiento.xml` | `Secuencia_Modulo2_Notificacion_Vencimiento.png` |
| Envío y resguardo de comprobante | `Secuencia_Modulo2_Envio_Comprobante.xml` | `Secuencia_Modulo2_Envio_Comprobante.png` |

Las fuentes XML utilizan el formato editable de diagrams.net y siguen la estructura visual de los diagramas existentes del proyecto.

## Trazabilidad de requerimientos

| Requisito | Representación principal | Diagramas de secuencia relacionados |
|---|---|---|
| RF-10 Recepción y respuesta automática | Recibir y responder mensajes, abrir o reutilizar conversación y procesar el mensaje entrante. | Recepción e identificación; consulta de cuenta; reclamo; comprobante. |
| RF-11 Reconocimiento de intención por IA | Reconocer intención, solicitar reformulación y derivar luego del segundo intento fallido. | Recepción, identificación, interpretación y escalado; registro de reclamo. |
| RF-12 Identificación del cliente | Identificar por teléfono o DNI y ofrecer registro y contratación cuando el cliente no existe. | Recepción e identificación; consulta de cuenta; reclamo; comprobante. |
| RF-13 Consulta de estado de cuenta | Consultar estado de cuenta según la intención reconocida. | Consulta de estado de cuenta por el bot. |
| RF-14 Registro de reclamos | Registrar reclamo o soporte e incluir la generación del ticket. | Registro de reclamo y generación de ticket. |
| RF-15 Escalado a usuario interno | Derivar después del segundo fallo y permitir que un empleado o administrador tome el control. | Recepción, identificación, interpretación y escalado. |
| RF-16 Historial de mensajes | Registrar cada mensaje y permitir consultar el historial completo. | Todos los diagramas registran los mensajes entrantes, salientes o de notificación que corresponden. |
| RF-17 Cierre de conversación | Cerrar la conversación resuelta por el bot o la atención escalada finalizada por un usuario interno. | Recepción, identificación, interpretación y escalado. |
| RF-18 Notificación de vencimientos | Notificar servicios próximos a vencer o vencidos mediante WhatsApp. | Notificación automática de vencimientos. |
| RF-19 Envío de comprobante | Recibir una imagen o PDF, validar el formato, almacenar el archivo y confirmar la recepción. | Envío y resguardo de comprobante. |

## Criterios aplicados

- WhatsApp Cloud API se representa como sistema externo; el bot y sus reglas forman parte del sistema.
- Las relaciones `include` señalan tareas obligatorias y las relaciones `extend` representan variantes condicionadas por la intención o el resultado del procesamiento.
- Los diagramas de secuencia utilizan marcos `alt`, `opt` y `loop` para mostrar decisiones, pasos opcionales y procesamiento repetido.
- RF-19 termina con el resguardo del comprobante. La extracción OCR, la detección de duplicados y la conciliación pertenecen a RF-20 y posteriores.
- Los PNG fueron revisados después de la exportación. Se ajustaron posiciones, recorridos, nombres largos y marcos para evitar elementos superpuestos y conservar la lectura de las flechas.

