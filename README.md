# Sistema de Gestión Integral Villafañe Wifi

Proyecto de Seminario de Integración de la Licenciatura en Sistemas de Información.

## Arquitectura inicial

El backend se implementa como un **monolito modular** con Django y Django REST
Framework. Cada aplicación representa un área funcional del negocio y se comunica
con las demás mediante servicios de aplicación bien definidos.

Los módulos iniciales son:

- `usuarios`: autenticación, empleados y administradores.
- `clientes`: clientes y medios de contacto.
- `servicios`: planes y conexiones contratadas.
- `conversaciones`: conversaciones y mensajes de WhatsApp.
- `facturacion`: cuotas, cuenta corriente y pagos acreditados.
- `pagos`: comprobantes, OCR y conciliación.
- `soporte`: tickets y notas internas.
- `reportes`: dashboard, reportes y alertas.
- `integraciones`: adaptadores para Meta, OCR, LLM y tareas externas.
- `comun`: componentes transversales sin reglas de negocio específicas.

## Preparación local

Requisitos: Python 3.12, PostgreSQL y Redis.

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r requirements\dev.txt
Copy-Item .env.example .env
```

Crear en PostgreSQL una base llamada `villafane_wifi` y ajustar `DATABASE_URL`
en `.env`. Luego ejecutar:

```powershell
Set-Location backend
python manage.py makemigrations usuarios
python manage.py migrate
python manage.py createsuperuser
python manage.py runserver
```

El endpoint `http://127.0.0.1:8000/health/` permite comprobar que el backend está activo.

Las reglas de idioma, documentación y POO están definidas en
[`docs/CONVENCIONES_CODIGO.md`](docs/CONVENCIONES_CODIGO.md).

## Estado de implementación

- **RF-01:** alta integral de cliente, teléfonos y servicios implementada.
- **RF-02:** consulta, edición y baja lógica de clientes implementadas.
- **RF-03:** búsqueda por documento, nombre, WhatsApp o localidad implementada.
- **RF-04:** catálogo de planes y asignación a conexiones implementados.
- **RF-29:** autenticación, cierre de sesión y protección del panel implementados.
- **RF-30:** matriz de acceso por subtipo y área implementada mediante políticas POO.

El proyecto no define una entidad `Rol`: `Usuario` se especializa de forma total y
disjunta en `Empleado` o `Administrador`. Las áreas de los empleados determinan sus
acciones permitidas sin modificar este modelo conceptual.

## Pruebas automatizadas

Las pruebas rápidas utilizan SQLite en memoria únicamente para aislar la lógica. El
sistema desplegado y las verificaciones de integración utilizarán PostgreSQL.

```powershell
.\.venv\Scripts\python.exe -m pytest -q
```

## Orden de implementación sugerido

1. Usuarios y accesos.
2. Clientes, planes y servicios.
3. Cuotas y cuenta corriente.
4. Conversaciones y mensajes.
5. Comprobantes y conciliación de pagos.
6. Tickets y notas internas.
7. Reportes e integraciones externas.
