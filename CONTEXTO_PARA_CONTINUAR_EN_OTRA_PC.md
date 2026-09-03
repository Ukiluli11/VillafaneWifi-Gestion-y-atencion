# Contexto para continuar el proyecto en otra PC

## Qué estamos desarrollando

Este repositorio corresponde al proyecto de **Seminario de Integración de la
Licenciatura en Sistemas de Información**. El objetivo es construir un sistema
de gestión integral y atención al cliente para **Villafañe Wifi**.

El sistema permitirá administrar clientes, conexiones contratadas, planes,
cuotas, pagos, comprobantes, conversaciones de WhatsApp, reclamos, tickets y
reportes. La aplicación se está desarrollando como un monolito modular con una
API central.

## Tecnologías y criterios obligatorios

- Python 3.12, Django y Django REST Framework.
- PostgreSQL como base de datos real.
- HTML, CSS y JavaScript para el panel web.
- Programación orientada a objetos.
- Clases, funciones y variables mayormente en español.
- Archivos, módulos, clases y funciones documentados adecuadamente.
- Bot de WhatsApp mediante Baileys durante el desarrollo y futura migración a
  Meta Cloud API.
- LLM externo para interpretar intenciones y Google Cloud Vision/Tesseract para
  procesar comprobantes.

Toda modificación debe mantener coherencia entre requisitos, documentación,
código, migraciones, base de datos y diagramas. No se debe agregar, eliminar o
renombrar una entidad en un solo artefacto sin revisar los demás.

## Estado actual

Se encuentra implementado el primer bloque funcional:

- Usuarios, autenticación y permisos por especialización.
- Clientes y teléfonos.
- Planes y servicios/conexiones contratadas.
- Cuenta corriente, cuotas, cuentas receptoras y pagos.
- Panel web y API REST para los flujos anteriores.
- Pruebas automatizadas del dominio, panel y API.

Requerimientos implementados: RF-01 a RF-06, RF-29 y RF-30.

`Usuario` es el supertipo y `Empleado` y `Administrador` son subtipos. No existe
una entidad de negocio llamada `Rol`. La especialización debe ser total y
disjunta.

PostgreSQL contiene actualmente **17 tablas**: 10 tablas del negocio y 7 tablas
técnicas generadas por Django. El modelo completo conserva además 5 tablas
futuras: `conversacion`, `mensaje`, `comprobante`, `ticket` y `nota_interna`.
Estas entidades no deben eliminarse de los diagramas; deben mantenerse marcadas
como futuras hasta que se creen sus modelos y migraciones.

## Archivos que deben consultarse primero

- `README.md`: preparación, módulos y estado funcional.
- `docs/CONVENCIONES_CODIGO.md`: reglas de programación y documentación.
- `docs/TRAZABILIDAD_MODELO_DATOS_Y_CLASES.md`: coincidencia entre PostgreSQL,
  Django, el DER y el diagrama de clases.
- `modelo_logico/DER_Logico_Completo_Villafane.drawio`: DER lógico editable.
- `modelo_logico/villafane_wifi_completo_mysql_workbench.sql`: script para
  importar el modelo completo en MySQL Workbench.
- `diagramas/Diagrama_de_clases_Villafane_CORREGIDO.drawio`: diagrama de clases.
- `backend/apps/`: código organizado por módulos de negocio.

El SQL de Workbench utiliza sintaxis MySQL solamente para diagramar. La base de
ejecución continúa siendo PostgreSQL y los modelos/migraciones Django son la
fuente de verdad de lo implementado.

## Puesta en marcha en Windows

Requisitos: Git, Python 3.12 y PostgreSQL.

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r requirements\dev.txt
Copy-Item .env.example .env
```

Crear una base PostgreSQL llamada `villafane_wifi`, completar `DATABASE_URL` en
`.env` y ejecutar:

```powershell
Set-Location backend
python manage.py migrate
python manage.py createsuperuser
python manage.py runserver
```

El sistema se abre en `http://127.0.0.1:8000/iniciar-sesion/`.

Para comprobar el proyecto:

```powershell
Set-Location ..
.\.venv\Scripts\python.exe -m pytest -q
.\.venv\Scripts\python.exe tools\verificar_alineacion_modelos.py
```

El segundo comando requiere que PostgreSQL local esté configurado y activo.

## Próximo bloque sugerido

El siguiente módulo es **Conversaciones y mensajes de WhatsApp**. Después se
implementarán comprobantes y conciliación, tickets y notas internas, reportes e
integraciones externas. Al implementar cada bloque futuro se deben crear los
modelos y migraciones correspondientes y actualizar el estado visual de los
diagramas sin alterar arbitrariamente los requisitos acordados.
