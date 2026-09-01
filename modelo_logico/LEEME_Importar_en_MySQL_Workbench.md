# DER lógico - Villafañe Wifi

## Importación en MySQL Workbench

1. Abrir MySQL Workbench.
2. Seleccionar `File > Import > Reverse Engineer MySQL Create Script`.
3. Elegir `villafane_wifi_mysql_workbench.sql`.
4. Confirmar la importación del esquema `villafane_wifi`.
5. Abrir el diagrama EER generado y usar `Arrange > Autolayout` si fuera necesario.
6. Guardar el modelo como archivo `.mwb`.

También puede crearse una conexión MySQL local, ejecutar el script y utilizar
`Database > Reverse Engineer` para obtener el mismo modelo desde el esquema.

## Criterios de transformación aplicados

- Cada entidad fuerte se transformó en una tabla con clave primaria propia.
- `USUARIO`, `EMPLEADO` y `ADMINISTRADOR` utilizan una tabla por tipo. La clave
  primaria de cada subtipo es también clave foránea hacia `USUARIO`.
- El atributo multivaluado `CLIENTE.Teléfono` se transformó en
  `CLIENTE_TELEFONO`, con clave primaria compuesta.
- Los atributos compuestos de documento y dirección se aplanaron en columnas
  simples.
- Las relaciones 1:N se transformaron propagando la clave primaria del lado 1
  como clave foránea en la tabla del lado N.
- En el diagrama, la cardinalidad se expresa únicamente con `1` y `N`, según
  la notación utilizada en la materia. La participación opcional se refleja en
  la nulabilidad de la clave foránea y no se escribe como cardinalidad cero.
- La relación `PAGO-CUOTA` se resolvió con `CUOTA.id_pago`: un pago puede
  cancelar varias cuotas y una cuota puede estar asociada como máximo a un pago.
- Los atributos de relaciones se trasladaron a `CONVERSACION`, `COMPROBANTE` y
  `TICKET` porque esas relaciones son 1:N.
- `Próximo vencimiento`, estado de cuota y cuenta corriente permanecen como
  datos derivados y no se almacenan directamente.
- Se agregó `MENSAJE.id_mensaje_externo` como clave alternativa opcional para
  evitar el procesamiento duplicado de eventos enviados por WhatsApp.

## Reglas que quedan en la aplicación

MySQL no puede expresar cómodamente algunas restricciones globales solo con
claves foráneas:

- Todo usuario debe pertenecer exclusivamente a `EMPLEADO` o `ADMINISTRADOR`.
- Las cuotas incluidas en un mismo pago deben corresponder al mismo cliente.
- Todo pago debe cancelar al menos una cuota y no se admiten pagos parciales.
- Las acciones de validación, atención y gestión requieren autorización.

El script está preparado para MySQL Workbench 8. El sistema definitivo seguirá
utilizando PostgreSQL mediante Django; por eso el modelo de Workbench se emplea
como documentación del diseño lógico y luego se traducirá a migraciones Django.
