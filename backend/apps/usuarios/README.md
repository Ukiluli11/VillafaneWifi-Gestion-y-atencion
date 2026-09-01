# Módulo de usuarios

Este módulo implementa RF-29 y sirve como base para RF-30.

## Responsabilidades

- almacenar las credenciales internas mediante `Usuario`;
- representar la especialización total y disjunta con `Empleado` y `Administrador`;
- crear ambos subtipos mediante `ServicioUsuarios`;
- iniciar y cerrar sesiones con vistas orientadas a objetos;
- impedir el acceso al panel cuando no existe una sesión válida.

No existe una entidad `Rol`. Los permisos funcionales se resolverán mediante políticas
orientadas a objetos según el subtipo y, para empleados, según su área.

## Rutas actuales

- `/iniciar-sesion/`: autenticación del usuario interno.
- `/cerrar-sesion/`: cierre seguro mediante una petición POST.
- `/`: pantalla inicial protegida.

## Pruebas

Desde la raíz del repositorio:

```powershell
.\.venv\Scripts\python.exe -m pytest backend\apps\usuarios -q
```

