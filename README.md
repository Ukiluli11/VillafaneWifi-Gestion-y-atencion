# Sistema de Gestión Integral Villafañe Wifi

Proyecto de Seminario de Integración de la Licenciatura en Sistemas de
Información. El repositorio está dividido en dos áreas:

- `sistema/`: aplicación ejecutable y código fuente.
- `teoria/`: documentación, diagramas y planificación vigentes.

## Stack vigente

PHP 8.3 o superior, Laravel 13, Blade, JavaScript, CSS, Vite y MariaDB 11.8.
La aplicación es un monolito modular con panel web, API REST, autenticación,
permisos, Eloquent ORM y migraciones.

## Alcance implementado

- Usuarios, autenticación y permisos por especialización.
- Clientes: alta, búsqueda, consulta, edición y baja lógica.
- Planes y servicios: alta, edición, asignación, suspensión y reactivación.
- Cuenta corriente: cuotas, deuda, vencimientos y pagos.
- Cuentas receptoras.
- Panel web y API REST versionada en `/api/v1`.

Las entidades futuras continúan en los diagramas, pero todavía no tienen tablas
ni pantallas.

## Iniciar en esta PC

Desde la raíz del repositorio:

```powershell
powershell -ExecutionPolicy Bypass -File sistema\tools\windows\iniciar-aplicacion.ps1
```

El panel queda disponible en `http://127.0.0.1:8000/iniciar-sesion`.

## Preparar en otra PC

```powershell
Set-Location sistema
Copy-Item .env.example .env
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan test
php artisan serve
```

Antes de ejecutar las migraciones se debe crear la base `villafane_wifi`,
completar las variables `DB_*` y definir las credenciales iniciales mediante
`ADMIN_INICIAL_*`.

## Documentación vigente

- `teoria/documentacion/Informe_general_Villafane_Wifi.docx`.
- `teoria/documentacion/CONTEXTO_PARA_CONTINUAR_EN_OTRA_PC.md`.
- `teoria/documentacion/TRAZABILIDAD_MODELO_DATOS_Y_CLASES.md`.
- `teoria/diagramas/` para las fuentes editables y vistas previas vigentes.
- `teoria/planificacion/Cronograma_Gantt.xlsx`.

No se conserva el prototipo anterior en Django ni las versiones intermedias de
los diagramas. Git mantiene el historial de los archivos que estuvieron
versionados.
