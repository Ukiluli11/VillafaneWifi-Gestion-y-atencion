# Modularización del sistema

## Criterio

La división se realiza por capacidades del negocio y no por tipos técnicos. Un módulo
es responsable de sus entidades y reglas; otro módulo no debe modificar directamente
sus datos sin pasar por una función o servicio público del módulo propietario.

## Dependencias permitidas

```text
users ───────────────────────────────┐
customers ── services ── billing ────┼── reporting
       └──── conversations ──────────┤
services ───────────── support ──────┤
conversations ─ payments ─ billing ──┘
integrations → conversations / payments
common → utilidades compartidas por todos
```

`reporting` puede leer información de otros módulos, pero no modificarla.
`integrations` traduce APIs externas a operaciones internas y no contiene reglas de negocio.

## Correspondencia con el DER

| Módulo | Entidades principales |
|---|---|
| users | Usuario, Empleado, Administrador |
| customers | Cliente, teléfonos |
| services | Plan, Servicio |
| conversations | Conversación, Mensaje |
| billing | Cuota, Pago, Cuenta receptora |
| payments | Comprobante y validación |
| support | Ticket, Nota interna |
| reporting | Consultas, indicadores y alertas derivadas |

## Regla para comenzar cada módulo

Antes de programarlo se revisarán los RF asociados, sus casos de uso y las tablas del
DER lógico. No es necesario terminar todos los diagramas del sistema para comenzar.

