# Módulo de clientes

Este módulo implementa RF-01, RF-02 y RF-03 en coordinación con `servicios`.

## Responsabilidades

- registrar personas o empresas mediante un documento único por tipo;
- almacenar uno o más teléfonos/WhatsApp normalizados por cliente;
- editar datos y reemplazar contactos sin duplicar el cliente;
- realizar bajas lógicas y solicitar la inactivación de sus conexiones;
- buscar por documento, nombre o razón social, teléfono y localidad;
- coordinar el alta integral con al menos un servicio contratado.

La dirección y el documento se almacenan en campos simples, tal como se decidió al
transformar el DER conceptual. El teléfono multivaluado se representa mediante
`TelefonoCliente` y cada número solo puede identificar a un cliente.

## API

- `GET /api/clientes/?buscar=texto`: lista o busca clientes.
- `POST /api/clientes/`: crea cliente, teléfonos y servicios en una transacción.
- `GET /api/clientes/{id}/`: consulta el detalle completo.
- `PUT/PATCH /api/clientes/{id}/`: modifica datos y, opcionalmente, teléfonos.
- `DELETE /api/clientes/{id}/`: realiza la baja lógica.
