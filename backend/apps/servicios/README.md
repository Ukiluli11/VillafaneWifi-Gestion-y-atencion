# Módulo de planes y servicios

Este módulo completa la parte de contratación de RF-01 e implementa RF-04.

## Responsabilidades

- gestionar el catálogo de planes, velocidad y precio vigente;
- evitar nombres duplicados aunque cambien mayúsculas y minúsculas;
- vincular cada conexión con un cliente y un plan;
- permitir varias conexiones para un mismo cliente;
- validar IP, MAC y día de vencimiento;
- realizar bajas lógicas de planes y servicios.

La baja de un plan no modifica los servicios históricos que ya lo tienen asignado,
pero impide nuevas contrataciones. La baja de un cliente inactiva sus conexiones.

## API

- `/api/planes/`: operaciones de consulta y gestión del catálogo.
- `/api/servicios/`: operaciones de consulta y gestión de conexiones.
- `/api/servicios/?id_cliente={id}`: conexiones de un cliente determinado.

## Panel web

- `/panel/planes/`: catálogo visual y gestión de planes.
- `/panel/conexiones/`: listado general de servicios contratados.
- `/panel/conexiones/nueva/`: contratación para un cliente existente.

Las operaciones de edición y baja son lógicas y solicitan confirmación cuando
corresponde.
