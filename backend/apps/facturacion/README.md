# Módulo de facturación y cuenta corriente

Este módulo implementa RF-05 y RF-06 mediante las entidades `Cuota`, `Pago` y
`CuentaReceptora`.

## Reglas principales

- cada servicio tiene como máximo una cuota por período `AAAA-MM`;
- el precio se copia a la cuota para conservar el valor histórico;
- los días 29, 30 y 31 se ajustan al último día de los meses más cortos;
- una cobranza cancela una o varias cuotas completas del mismo cliente;
- una transferencia puede incluir cuotas de diferentes servicios;
- una cuota pagada no puede volver a imputarse;
- el saldo y el estado se calculan desde las cuotas y no se guardan de forma redundante.

El comprobante, su detección de duplicados y la validación OCR se incorporarán en el
módulo `pagos` al implementar RF-14 a RF-20. La carga manual actual permite avanzar
con la cuenta corriente sin mezclar esa responsabilidad futura.

## API

- `GET /api/cuotas/?id_cliente={id}`: historial de cuotas de un cliente.
- `POST /api/cuotas/generar/`: generación mensual para servicios activos.
- `GET /api/pagos/?id_cliente={id}`: historial de pagos.
- `POST /api/pagos/`: registro de un pago con `ids_cuotas`, `id_cuenta` y `medio_pago`.

## Panel web

- `/panel/cuentas/`: situación de todos los clientes.
- `/panel/cuentas/{id}/`: deuda, vencimientos, cuotas y pagos del cliente.
- `/panel/cuentas/{id}/registrar-pago/`: imputación de una cobranza.
- `/panel/cuentas/generar-cuotas/`: facturación mensual en bloque.
- `/panel/cuentas/cuentas-receptoras/`: cuentas habilitadas para recibir pagos.
