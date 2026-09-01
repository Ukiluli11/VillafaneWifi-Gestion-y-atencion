# Módulo de usuarios

Este módulo implementa RF-29 y RF-30.

## Responsabilidades

- almacenar las credenciales internas mediante `Usuario`;
- representar la especialización total y disjunta con `Empleado` y `Administrador`;
- crear ambos subtipos mediante `ServicioUsuarios`;
- iniciar y cerrar sesiones con vistas orientadas a objetos;
- impedir el acceso al panel cuando no existe una sesión válida.
- autorizar funciones mediante `ServicioAutorizacion` y políticas por área;
- proteger vistas funcionales reutilizando `AccionRequeridaMixin`.

No existe una entidad `Rol`. Los permisos funcionales se resuelven mediante políticas
orientadas a objetos según el subtipo y, para empleados, según su área. La política
también rechaza usuarios inactivos, sin subtipo o con dos subtipos simultáneos.

## Matriz de acceso inicial

- **Administración:** clientes, planes, servicios, cuentas, pagos y reportes.
- **Soporte técnico:** consulta de clientes y servicios, conversaciones y tickets.
- **Atención al cliente:** consulta de clientes y cuentas, conversaciones y alta de tickets.
- **Administrador:** acceso completo, incluida la gestión de usuarios.

Cada vista funcional deberá declarar una `AccionSistema` mediante
`AccionRequeridaMixin`. Esto evita repartir comparaciones de áreas por todo el código.

## Rutas actuales

- `/iniciar-sesion/`: autenticación del usuario interno.
- `/cerrar-sesion/`: cierre seguro mediante una petición POST.
- `/`: pantalla inicial protegida.

## Pruebas

Desde la raíz del repositorio:

```powershell
.\.venv\Scripts\python.exe -m pytest backend\apps\usuarios -q
```
