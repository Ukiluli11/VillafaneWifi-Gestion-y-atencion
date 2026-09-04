# Trazabilidad del modelo de datos y clases

## Criterio vigente

Los diagramas entregados por el equipo definen el alcance conceptual y se
preservan en `teoria/diagramas/`. Para el alcance actual, las clases
Eloquent, la migración Laravel y el esquema MariaDB deben coincidir con las
entidades marcadas como actuales. Las cinco entidades futuras permanecen en los
diagramas sin crear tablas prematuramente.

Se tomó la clave identificadora simple de cada entidad, tal como expresa el
diagrama de clases. Los campos que el archivo de Workbench mostraba
simultáneamente como `NOT NULL` y `DEFAULT NULL` se resolvieron conforme a su
participación opcional indicada en el dominio.

## Correspondencia implementada

| Entidad del diagrama | Clase Laravel | Tabla MariaDB | Estado |
|---|---|---|---|
| Usuario | `App\\Models\\Usuario` | `usuario` | Implementada |
| Administrador | `App\\Models\\Administrador` | `administrador` | Implementada |
| Empleado | `App\\Models\\Empleado` | `empleado` | Implementada |
| Cliente | `App\\Models\\Cliente` | `cliente` | Implementada |
| Plan | `App\\Models\\Plan` | `plan` | Implementada |
| Servicio | `App\\Models\\Servicio` | `servicio` | Implementada |
| Cuenta receptora | `App\\Models\\CuentaReceptora` | `cuenta_receptora` | Implementada |
| Pago | `App\\Models\\Pago` | `pago` | Implementada |
| Cuota | `App\\Models\\Cuota` | `cuota` | Implementada |

La tabla `migrations` es infraestructura de Laravel y no una entidad del
negocio. Por eso el esquema operativo contiene nueve tablas de dominio y una
tabla técnica.

## Entidades futuras preservadas

| Entidad | Finalidad prevista | Estado |
|---|---|---|
| Conversación | Atención por WhatsApp y transferencia a una persona. | Futura |
| Mensaje | Mensajes, archivos y emisor de una conversación. | Futura |
| Comprobante | OCR, duplicados y validación de pagos. | Futura |
| Ticket | Reclamos, cola y responsable de atención. | Futura |
| Nota interna | Seguimiento interno de tickets. | Futura |

`pago.id_comprobante` existe como campo nullable, pero no tiene clave foránea
hasta implementar `comprobante`.

## Reglas alineadas

1. Todo usuario activo pertenece exclusivamente a `Empleado` o
   `Administrador`; las claves de los subtipos también son FK a `usuario`.
2. El documento de cliente es único por tipo y número.
3. Un cliente puede contratar varios servicios; cada servicio usa un plan.
4. Un servicio genera como máximo una cuota por período.
5. Una cuota puede estar pendiente, pagada o vencida; un pago puede cancelar
   varias cuotas completas del mismo cliente.
6. La dirección de contacto y la de instalación se almacenan por separado.
7. IP y MAC son opcionales. El día de vencimiento está limitado del 1 al 28 por
   validación de aplicación.
8. `proximo_vencimiento` se mantiene como valor derivado almacenado porque así
   aparece en el DER recibido; el servicio de facturación lo recalcula.
9. Los permisos se resuelven mediante clases de autorización según subtipo,
   área y banderas del administrador.

## Artefactos autoritativos y ejecutables

- `teoria/diagramas/DER_logico.mwb`.
- `teoria/diagramas/Diagrama_de_clases.drawio`.
- `teoria/diagramas/Diagrama_casos_de_uso.drawio`.
- `sistema/database/migrations/2026_09_03_062716_create_modulo_uno_tables.php`.
- `sistema/app/Models/` y `sistema/app/Dominio/`.
- `sistema/tests/`.

Al implementar una entidad futura se deben actualizar juntos sus requisitos,
clase, migración, pruebas, DER, diagrama de clases y este documento.
