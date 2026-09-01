# Sistema de Gestión Integral Villafañe Wifi

Proyecto de Seminario de Integración de la Licenciatura en Sistemas de Información.

## Arquitectura inicial

El backend se implementa como un **monolito modular** con Django y Django REST
Framework. Cada aplicación representa un área funcional del negocio y se comunica
con las demás mediante servicios de aplicación bien definidos.

Los módulos iniciales son:

- `users`: autenticación, empleados y administradores.
- `customers`: clientes y medios de contacto.
- `services`: planes y conexiones contratadas.
- `conversations`: conversaciones y mensajes de WhatsApp.
- `billing`: cuotas, cuenta corriente y pagos acreditados.
- `payments`: comprobantes, OCR y conciliación.
- `support`: tickets y notas internas.
- `reporting`: dashboard, reportes y alertas.
- `integrations`: adaptadores para Meta, OCR, LLM y tareas externas.
- `common`: componentes transversales sin reglas de negocio específicas.

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
python manage.py makemigrations users
python manage.py migrate
python manage.py createsuperuser
python manage.py runserver
```

El endpoint `http://127.0.0.1:8000/health/` permite comprobar que el backend está activo.

## Orden de implementación sugerido

1. Usuarios y accesos.
2. Clientes, planes y servicios.
3. Cuotas y cuenta corriente.
4. Conversaciones y mensajes.
5. Comprobantes y conciliación de pagos.
6. Tickets y notas internas.
7. Reportes e integraciones externas.

