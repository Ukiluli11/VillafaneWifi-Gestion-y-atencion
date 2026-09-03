# Trazabilidad del modelo de datos y del diagrama de clases

## Criterio adoptado

El motor real del sistema es **PostgreSQL** y el ORM de **Django** es la fuente
de verdad para las tablas ya implementadas. MySQL Workbench se utiliza solamente
para editar y visualizar el DER lógico; por esa razón el script de importación
usa sintaxis MySQL equivalente, pero conserva los nombres de tablas, columnas,
claves primarias y claves foráneas del proyecto Django.

Para no presentar como terminada una función que todavía no existe, los
diagramas separan el modelo en tres estados:

- **Implementado (verde):** existe hoy en los modelos, migraciones y PostgreSQL.
- **Futuro planificado (naranja):** está definido por los requisitos y se
  implementará en los módulos siguientes; no se elimina del modelo completo.
- **Infraestructura Django (gris):** tablas y clases técnicas creadas o usadas
  por autenticación, permisos, sesiones, migraciones y administración.

Un campo `NULL` representa participación opcional en el modelo lógico. No se
emplea cardinalidad cero: las relaciones se expresan como `1:1`, `1:N` o `N:1`.

## Tablas actualmente existentes en PostgreSQL

| Área | Tablas |
|---|---|
| Usuarios | `usuario`, `empleado`, `administrador` |
| Clientes | `cliente`, `cliente_telefono` |
| Servicios | `plan`, `servicio` |
| Facturación | `cuenta_receptora`, `pago`, `cuota` |
| Django | `django_migrations`, `django_content_type`, `django_session`, `django_admin_log`, `auth_permission`, `auth_group`, `auth_group_permissions` |

Son **17 tablas actuales**: 10 del dominio y 7 de infraestructura Django.
`Empleado` y `Administrador` son especializaciones disjuntas de `Usuario` y sus
claves `id_usuario` son simultáneamente PK y FK, por lo que cada vínculo es 1:1.

## Entidades planificadas que se conservan

| Entidad futura | Finalidad | Relaciones principales |
|---|---|---|
| `conversacion` | Registrar cada atención por WhatsApp y el traspaso del bot a una persona. | Cliente 1:N Conversación; Usuario 1:N Conversación atendida. |
| `mensaje` | Conservar cada mensaje, archivo y emisor de una conversación. | Conversación 1:N Mensaje; Usuario 1:N Mensaje emitido. |
| `comprobante` | Evitar duplicados, guardar OCR y registrar validación. | Mensaje 1:1 Comprobante; Usuario 1:N Comprobante validado. |
| `ticket` | Administrar reclamos en orden de llegada y su responsable. | Conversación 1:N Ticket; Servicio 1:N Ticket; Usuario 1:N Ticket atendido. |
| `nota_interna` | Guardar observaciones internas de seguimiento. | Ticket 1:N Nota; Usuario 1:N Nota. |

El modelo completo contiene **22 tablas**: las 17 actuales más estas 5 tablas
futuras. Además, `pago.id_comprobante` está marcado como ampliación futura. Es
nullable porque los pagos manuales o en efectivo pueden no tener comprobante.

## Reglas que deben mantenerse sincronizadas

1. Un cliente puede contratar varios servicios y cada servicio utiliza un plan.
2. Cada servicio genera cuotas. Una cuota pendiente todavía no referencia un
   pago; una transferencia puede cancelar una o varias cuotas completas.
3. La dirección de contacto y la dirección de instalación se almacenan mediante
   campos simples, no como atributos compuestos.
4. IP y MAC identifican técnicamente una conexión cuando están disponibles.
5. Un ticket queda en cola hasta que el primer usuario habilitado lo toma.
6. Empleados y administradores pueden validar comprobantes según sus permisos.
7. Estado de cuota, saldo y próximo vencimiento son valores calculados, no
   columnas redundantes.
8. Las tablas técnicas de Django se documentan, pero no sustituyen a las
   entidades del negocio ni justifican cambiar PostgreSQL por MariaDB.

## Artefactos autoritativos

- `backend/apps/*/models.py`: implementación actual del dominio.
- `backend/apps/*/migrations/`: historial reproducible de la base PostgreSQL.
- `modelo_logico/villafane_wifi_completo_mysql_workbench.sql`: importación del
  modelo completo en MySQL Workbench.
- `modelo_logico/DER_Logico_Completo_Villafane.drawio`: DER lógico editable.
- `diagramas/Diagrama_de_clases_Villafane_CORREGIDO.drawio`: clases de dominio,
  servicios, panel Django y API REST.
- `tools/verificar_alineacion_modelos.py`: control automático de coincidencia
  entre PostgreSQL, SQL de Workbench y pestañas de los diagramas.

Cuando se implemente una entidad futura, se debe crear primero su modelo y su
migración, ejecutar la verificación y cambiar su color/estado en los diagramas.
