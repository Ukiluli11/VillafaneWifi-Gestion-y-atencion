# Aplicación Villafañe Wifi

Implementación vigente del sistema con Laravel 13, PHP, JavaScript y MariaDB.

## Organización

- `app/Models`: entidades del DER implementadas con Eloquent.
- `app/Dominio`: servicios que coordinan reglas y casos de uso.
- `app/Autorizacion`: políticas POO para administradores y empleados.
- `app/Http/Controllers/Web`: panel administrativo.
- `app/Http/Controllers/Api/V1`: API REST versionada.
- `database/migrations`: esquema reproducible de MariaDB.
- `resources/views`: pantallas Blade del panel.
- `tests`: pruebas funcionales y de dominio.

La preparación completa se explica en el `README.md` de la raíz. El alcance,
los diagramas y la trazabilidad se encuentran en `../teoria/`.
