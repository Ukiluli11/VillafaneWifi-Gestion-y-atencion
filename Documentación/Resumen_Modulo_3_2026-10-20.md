# Módulo 3 — Pagos y comprobantes

Estado al 20/10/2026. Proyecto de demostración académica.

## Qué hicimos y cómo funciona

1. El cliente consulta sus cuotas y los datos de la cuenta receptora por WhatsApp. Todos los clientes reciben el mismo destino de Mercado Pago, configurado como cuenta de la empresa.
2. El cliente transfiere y adjunta una imagen o PDF. El sistema guarda el original de forma privada y detecta archivos idénticos ya registrados.
3. Tesseract intenta extraer importe, fecha y número de operación. Los datos quedan disponibles en el panel para revisión; si la lectura falla, pueden cargarse manualmente.
4. Un empleado comprueba el ingreso en Mercado Pago y aprueba o rechaza el comprobante. La aprobación registra el pago y cancela cuotas completas, comenzando por las más antiguas; el rechazo conserva el motivo.
5. El sistema registra el resultado e intenta avisar al cliente. Un fallo del aviso no deshace el pago y permite reintentar la notificación sin volver a acreditar el importe.

## Qué verificamos

- Recepción de imágenes y PDF, OCR y carga manual cuando la lectura no resulta útil.
- Detección de archivos duplicados y controles para evitar registrar nuevamente una operación en la misma cuenta receptora.
- Aprobación, rechazo, actualización de cuotas y avisos dentro de la ventana de atención.
- Lectura del formato ARQ y una transferencia real de **$100**, cuyo ingreso fue confirmado por el usuario antes de aprobarla. Se registró el pago y se canceló la cuota correspondiente.
- Planes con precios de demostración de **$100, $150 y $200**. Los pagos históricos conservan sus importes.

## Limitaciones y pendientes

- La recepción es por **transferencia a alias/CVU con revisión manual**. El sistema no consulta movimientos de Mercado Pago ni confirma automáticamente que ingresó dinero. Las transferencias realizadas son dinero real, aunque el proyecto sea académico.
- El OCR ayuda a cargar datos, pero no verifica la autenticidad del comprobante ni la acreditación bancaria. Puede confundir caracteres; siempre hay que comparar con el original.
- En PDF se lee solamente la primera página; se conserva el documento completo. Se admiten imágenes JPEG/PNG y PDF de hasta 10 MB.
- Solo se aceptan importes que cancelen cuotas completas desde las más antiguas. No se admiten pagos parciales ni saldos a favor.
- Falta probar los avisos de aprobación y rechazo fuera de las 24 horas. Dependen de las plantillas de Meta, todavía en revisión, y de habilitar los envíos dentro de los límites de consumo.

**Estado:** circuito principal validado para la demostración académica. Queda pendiente la comprobación de avisos fuera de las 24 horas, compartida con M2.
