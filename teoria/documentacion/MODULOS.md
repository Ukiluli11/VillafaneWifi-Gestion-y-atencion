# Modularización del sistema

## Criterio

La división se realiza por capacidades del negocio y no por tipos técnicos. Un módulo
es responsable de sus entidades y reglas; otro módulo no debe modificar directamente
sus datos sin pasar por una función o servicio público del módulo propietario.

## Dependencias permitidas

```text
usuarios ──────────────────────────────────┐
clientes ── servicios ── facturacion ──────┼── reportes
      └──── conversaciones ────────────────┤
servicios ───────────── soporte ───────────┤
conversaciones ─ pagos ─ facturacion ──────┘
integraciones → conversaciones / pagos
comun → utilidades compartidas por todos
```

`reportes` puede leer información de otros módulos, pero no modificarla.
`integraciones` traduce APIs externas a operaciones internas y no contiene reglas de negocio.

## Correspondencia con el DER

| Módulo | Entidades principales |
|---|---|
| usuarios | Usuario, Empleado, Administrador |
| clientes | Cliente y datos de contacto |
| servicios | Plan, Servicio |
| conversaciones | Conversación, Mensaje |
| facturacion | Cuota, Pago, Cuenta receptora |
| pagos | Comprobante y validación |
| soporte | Ticket, Nota interna |
| reportes | Consultas, indicadores y alertas derivadas |

## Regla para comenzar cada módulo

Antes de programarlo se revisarán los RF asociados, sus casos de uso y las tablas del
DER lógico. No es necesario terminar todos los diagramas del sistema para comenzar.

## Estado al 16/09/2026

- El Módulo 1 se encuentra implementado y probado.
- El Módulo 2 cuenta con tablas, modelos, enumerados, relaciones y servicios de
  dominio de Conversación y Mensaje.
- El webhook de Meta identifica al cliente, evita mensajes duplicados, conserva
  el historial y muestra el menú inicial.
- Toda respuesta automática comienza con un saludo y se identifica como el
  servicio virtual de Villafañe Wifi; la regla se aplica desde el servicio de
  salida para que ningún caso de uso pueda omitirla.
- La opción 1 consulta servicios, cuotas pendientes, deuda vencida y próximo
  vencimiento directamente desde la cuenta corriente del cliente.
- La opción 2 solicita una imagen o PDF, conserva el mensaje recibido y crea el
  comprobante con estado pendiente de validación.
- El panel posee una bandeja de comprobantes, con búsqueda, filtro por estado y
  acceso al historial de la conversación de origen.
- La descarga del archivo desde Meta, el OCR y la aprobación o rechazo por un
  usuario autorizado constituyen el siguiente incremento del Módulo 2.
- Los reclamos y la atención humana continúan después del flujo de pagos.
