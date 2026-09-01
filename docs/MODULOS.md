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
| clientes | Cliente, teléfonos |
| servicios | Plan, Servicio |
| conversaciones | Conversación, Mensaje |
| facturacion | Cuota, Pago, Cuenta receptora |
| pagos | Comprobante y validación |
| soporte | Ticket, Nota interna |
| reportes | Consultas, indicadores y alertas derivadas |

## Regla para comenzar cada módulo

Antes de programarlo se revisarán los RF asociados, sus casos de uso y las tablas del
DER lógico. No es necesario terminar todos los diagramas del sistema para comenzar.
