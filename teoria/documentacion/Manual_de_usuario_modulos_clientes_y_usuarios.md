# Manual de usuario
## Sistema de Gestión Villafañe Wifi
### Módulos: Gestión de Clientes · Usuarios y Accesos

**Versión:** 1.0  
**Fecha:** Septiembre 2026  
**Autores:** Ulises Villafañe · Agustín Villafañe  
**Materia:** Seminario de Integración — Licenciatura en Sistemas de Información

---

## Índice

1. [Introducción al sistema](#1-introducción-al-sistema)
2. [Acceso al sistema](#2-acceso-al-sistema)
3. [Panel de inicio](#3-panel-de-inicio)
4. [Tipos de usuario y permisos](#4-tipos-de-usuario-y-permisos)
5. [Módulo: Gestión de Clientes](#5-módulo-gestión-de-clientes)
   - 5.1 [Consultar y buscar clientes](#51-consultar-y-buscar-clientes)
   - 5.2 [Ver la ficha de un cliente](#52-ver-la-ficha-de-un-cliente)
   - 5.3 [Registrar un nuevo cliente](#53-registrar-un-nuevo-cliente)
   - 5.4 [Editar los datos de un cliente](#54-editar-los-datos-de-un-cliente)
   - 5.5 [Dar de baja a un cliente](#55-dar-de-baja-a-un-cliente)
6. [Módulo: Usuarios y Accesos](#6-módulo-usuarios-y-accesos)
   - 6.1 [Ver el listado de usuarios](#61-ver-el-listado-de-usuarios)
   - 6.2 [Crear un nuevo usuario](#62-crear-un-nuevo-usuario)
   - 6.3 [Desactivar un usuario](#63-desactivar-un-usuario)

---

## 1. Introducción al sistema

El sistema de gestión de Villafañe Wifi es una aplicación web de uso interno que centraliza la administración de clientes, servicios de internet, planes, cobranzas y el control de accesos del personal.

El sistema está dividido en módulos funcionales. Este manual cubre los dos primeros módulos implementados:

- **Gestión de Clientes:** permite registrar, consultar, editar y dar de baja a los clientes de la empresa.
- **Usuarios y Accesos:** permite crear y administrar las cuentas del personal que opera el sistema.

El acceso se realiza desde un navegador web, ingresando a la dirección que le indique el administrador del sistema. No requiere instalar ningún programa adicional.

---

## 2. Acceso al sistema

### 2.1 Iniciar sesión

Para ingresar al sistema, abra su navegador y acceda a la dirección del panel. Verá la pantalla de inicio de sesión:

![Pantalla de inicio de sesión del sistema Villafañe Wifi](C:\Users\Usuario\.gemini\antigravity\brain\240960ca-6e35-433a-885f-1cbccc034be2\captura_login_1788500115572.jpg)

Complete los siguientes campos:

| Campo | Descripción |
|---|---|
| **Nombre de usuario** | El nombre de usuario asignado por el administrador. |
| **Contraseña** | La contraseña personal de su cuenta. |

Haga clic en **Iniciar sesión** para acceder.

> **Nota:** Si ingresa credenciales incorrectas, el sistema mostrará un mensaje de error. Después de 5 intentos fallidos en menos de un minuto, el sistema bloqueará temporalmente los intentos por seguridad. Si olvidó su contraseña, contacte al administrador del sistema.

### 2.2 Cerrar sesión

Para cerrar su sesión de forma segura, haga clic en el ícono de salida (→) ubicado en la parte inferior de la barra lateral izquierda, junto a su nombre de usuario. El sistema lo redirigirá a la pantalla de inicio de sesión.

---

## 3. Panel de inicio

Luego de iniciar sesión, el sistema muestra el **panel de inicio**, que brinda una vista general del estado actual de la operación:

![Panel de inicio con indicadores y accesos rápidos](C:\Users\Usuario\.gemini\antigravity\brain\240960ca-6e35-433a-885f-1cbccc034be2\captura_panel_inicio_1788500266638.jpg)

### 3.1 Indicadores principales

En la parte superior del panel se muestran cuatro indicadores de situación:

| Indicador | Descripción |
|---|---|
| **Clientes activos** | Cantidad de clientes con estado activo en el sistema. |
| **Servicios activos** | Cantidad de conexiones de internet operativas. |
| **Cuotas por cobrar** | Cuotas pendientes de pago o vencidas. |
| **Deuda vencida** | Monto total de cuotas vencidas sin pagar. |

### 3.2 Accesos rápidos

Debajo de los indicadores aparece un conjunto de accesos rápidos a las tareas más frecuentes. Los botones disponibles dependen de los permisos del usuario:

- **Registrar cliente** — abre el formulario para agregar un nuevo cliente.
- **Asignar servicio** — abre el formulario para contratar un servicio para un cliente.
- **Buscar cliente** — va directamente al listado de clientes.
- **Gestionar cobranza** — va a la sección de cuentas receptoras.

### 3.3 Navegación lateral

La barra lateral izquierda está siempre visible y permite acceder a las diferentes secciones del sistema. Solo aparecen las secciones a las que tiene acceso según su perfil. Las secciones disponibles son:

- **Resumen** — panel de inicio.
- **Clientes** — gestión de fichas de clientes.
- **Servicios** — administración de contratos de internet.
- **Planes** — configuración de planes tarifarios.
- **Cobranza** — cuentas receptoras de pagos.
- **Usuarios** — administración de usuarios del sistema (solo para administradores con ese permiso).

---

## 4. Tipos de usuario y permisos

El sistema distingue dos tipos principales de usuarios: **Administrador** y **Empleado**. Dentro de los empleados, el acceso varía según el área a la que pertenecen.

### 4.1 Administrador

El administrador tiene acceso amplio al sistema. Existen dos permisos adicionales que el propio administrador puede tener habilitados o no:

| Permiso | Efecto cuando está habilitado |
|---|---|
| `puede_gestionar_usuarios` | Puede ver y gestionar la sección Usuarios y Accesos. |
| `puede_configurar_planes` | Puede crear y editar planes tarifarios. |

Un administrador sin el permiso de gestionar usuarios no verá la sección Usuarios en el menú.

### 4.2 Empleados por área

Los empleados tienen acceso restringido según su área de trabajo:

| Área | Clientes | Servicios | Planes | Cobranza | Usuarios |
|---|---|---|---|---|---|
| **Administración** | Ver + Gestionar | Ver + Gestionar | Ver + Gestionar | Ver + Gestionar | ✗ |
| **Soporte** | Solo ver | Solo ver | ✗ | ✗ | ✗ |
| **Atención al cliente** | Solo ver | ✗ | ✗ | Solo ver | ✗ |

> **Nota importante:** Ningún empleado puede acceder a la gestión de usuarios. Esa sección es exclusiva del administrador con el permiso correspondiente.

### 4.3 Resumen de acceso a funciones de clientes

| Acción | Administrador | Empleado Administración | Empleado Soporte | Empleado Atención |
|---|---|---|---|---|
| Ver listado de clientes | ✓ | ✓ | ✓ | ✓ |
| Buscar clientes | ✓ | ✓ | ✓ | ✓ |
| Ver ficha de cliente | ✓ | ✓ | ✓ | ✓ |
| Registrar cliente | ✓ | ✓ | ✗ | ✗ |
| Editar cliente | ✓ | ✓ | ✗ | ✗ |
| Dar de baja cliente | ✓ | ✓ | ✗ | ✗ |

---

## 5. Módulo: Gestión de Clientes

El módulo de Gestión de Clientes permite administrar toda la información de los clientes de Villafañe Wifi, incluyendo sus datos de contacto y los servicios de internet que tienen contratados.

### 5.1 Consultar y buscar clientes

Acceda a **Clientes** en la barra lateral. El sistema muestra el listado completo de clientes, ordenado alfabéticamente:

![Listado de clientes con buscador y tabla de resultados](C:\Users\Usuario\.gemini\antigravity\brain\240960ca-6e35-433a-885f-1cbccc034be2\captura_clientes_listado_1788500344339.jpg)

La tabla muestra los siguientes datos de cada cliente:

| Columna | Contenido |
|---|---|
| **Cliente** | Nombre o razón social y tipo (Particular / Comercio). |
| **Documento** | Tipo de documento (DNI / CUIT / CUIL) y número. |
| **WhatsApp** | Número de contacto por WhatsApp, si fue cargado. |
| **Localidad** | Localidad de contacto. |
| **Estado** | Estado actual: Activo, Suspendido o Baja. |

#### Buscar clientes

Utilice el campo de búsqueda ubicado debajo del encabezado. Puede buscar por:

- **Nombre o razón social** (búsqueda parcial, no distingue mayúsculas).
- **Número de documento** (DNI, CUIT o CUIL).
- **Número de WhatsApp**.
- **Localidad**.

Escriba el texto y haga clic en **Buscar**. El sistema filtra los resultados. Para ver todos los clientes nuevamente, borre el campo y haga clic en Buscar.

Si la lista supera los 20 clientes, aparece una barra de paginación en la parte inferior de la tabla para navegar entre páginas.

### 5.2 Ver la ficha de un cliente

Desde el listado, haga clic en **Ver ficha →** en la fila del cliente que desea consultar. El sistema muestra la ficha completa del cliente:

![Ficha detallada de un cliente con datos de contacto y servicios](C:\Users\Usuario\.gemini\antigravity\brain\240960ca-6e35-433a-885f-1cbccc034be2\captura_clientes_ficha_1788500512121.jpg)

La ficha contiene:

- **Encabezado:** nombre o razón social, tipo y número de documento, y estado actual.
- **Contacto:** número de WhatsApp y dirección postal de contacto.
- **Servicios contratados:** tabla con los servicios de internet del cliente, indicando el plan contratado, la dirección de instalación, el día de vencimiento mensual y el estado del servicio.

Los botones disponibles en la ficha varían según los permisos del usuario:

- **Editar** — abre el formulario para modificar los datos del cliente (solo para usuarios con permiso de gestión).
- **Cuenta corriente** — accede al estado de cuenta del cliente, con cuotas y pagos (disponible para usuarios con acceso a cobranza).
- **Dar de baja** — realiza la baja del cliente (solo para usuarios con permiso de gestión).

### 5.3 Registrar un nuevo cliente

> Disponible para: Administrador y Empleado de Administración.

Haga clic en el botón **+ Nuevo cliente** en el listado, o en **Registrar cliente** en los accesos rápidos del panel de inicio. Se abre el formulario de registro:

![Formulario de registro de nuevo cliente](C:\Users\Usuario\.gemini\antigravity\brain\240960ca-6e35-433a-885f-1cbccc034be2\captura_clientes_formulario_1788500470338.jpg)

Complete los campos del formulario:

| Campo | Obligatorio | Descripción |
|---|---|---|
| **Tipo de documento** | Sí | Seleccione DNI, CUIT o CUIL según corresponda. |
| **Número** | Sí | Ingrese solo los dígitos, sin puntos ni guiones. |
| **Nombre o razón social** | Sí | Nombre completo de la persona o nombre legal del comercio. |
| **Tipo de cliente** | Sí | Seleccione "Particular" para personas físicas o "Comercio" para empresas. |
| **WhatsApp** | No | Número de contacto. Ingrese solo dígitos, incluyendo el código de área. |
| **Calle de contacto** | No | Nombre de la calle de la dirección postal de contacto. |
| **Número** | No | Número de la dirección de contacto. |
| **Localidad** | No | Localidad de la dirección de contacto. |

Una vez completados los datos obligatorios, haga clic en **Guardar**. El sistema registra al cliente y lo redirige automáticamente a la ficha del nuevo cliente con un mensaje de confirmación.

Si hay algún error en los datos (por ejemplo, ya existe un cliente con el mismo documento), el sistema mostrará un aviso indicando qué campo debe corregirse.

Para cancelar sin guardar, haga clic en **Cancelar** y regresará al listado.

### 5.4 Editar los datos de un cliente

> Disponible para: Administrador y Empleado de Administración.

Desde la ficha del cliente, haga clic en **Editar**. El sistema abre el mismo formulario del registro, pero con los datos actuales del cliente precargados.

Modifique los campos que necesite actualizar. En la edición, aparece un campo adicional:

| Campo | Descripción |
|---|---|
| **Estado** | Permite cambiar el estado del cliente: Activo, Suspendido o Baja. |

Haga clic en **Guardar** para confirmar los cambios. El sistema lo redirige a la ficha actualizada del cliente.

### 5.5 Dar de baja a un cliente

> Disponible para: Administrador y Empleado de Administración.

La baja de un cliente es una operación que cancela todos sus servicios de internet activos. Antes de realizarla, verifique que sea la acción correcta.

Desde la ficha del cliente, haga clic en el botón **Dar de baja** (en rojo, al pie de la página). El sistema mostrará un mensaje de confirmación preguntando: *"¿Dar de baja al cliente y sus servicios?"*

- Haga clic en **Aceptar** para confirmar la baja.
- Haga clic en **Cancelar** para volver a la ficha sin realizar cambios.

Al confirmar, el sistema:
1. Cambia el estado del cliente a **Baja**.
2. Cambia el estado de todos sus servicios activos o suspendidos a **Baja**.
3. Lo redirige al listado de clientes con un mensaje de confirmación.

> **Importante:** La baja es lógica: el cliente y su historial se conservan en el sistema. Si necesita reactivar un cliente dado de baja, utilice la función **Editar** y cambie el estado manualmente.

---

## 6. Módulo: Usuarios y Accesos

El módulo de Usuarios y Accesos permite gestionar las cuentas del personal que utiliza el sistema. Desde aquí se crean nuevos usuarios, se configuran sus permisos según su rol y área, y se pueden desactivar cuentas cuando ya no son necesarias.

> **Acceso restringido:** Este módulo solo está disponible para administradores que tengan habilitado el permiso de gestionar usuarios. Los empleados no tienen acceso a esta sección.

### 6.1 Ver el listado de usuarios

Acceda a **Usuarios** en la barra lateral. El sistema muestra en la parte superior un formulario para crear nuevos usuarios, y debajo una tabla con todos los usuarios existentes:

![Panel de gestión de usuarios con formulario de creación y listado](C:\Users\Usuario\.gemini\antigravity\brain\240960ca-6e35-433a-885f-1cbccc034be2\captura_usuarios_panel_1788500703188.jpg)

La tabla muestra:

| Columna | Contenido |
|---|---|
| **Usuario** | Nombre de usuario con el que ingresa al sistema. |
| **Tipo** | Administrador o Empleado. |
| **Área / nivel** | Para empleados: el área asignada. Para administradores: el nivel de acceso. |
| **Estado** | Activo o Inactivo. |

### 6.2 Crear un nuevo usuario

Complete el formulario en la parte superior de la página Usuarios y permisos.

#### Campos comunes para todos los usuarios

| Campo | Obligatorio | Descripción |
|---|---|---|
| **Nombre de usuario** | Sí | Identificador único con el que el usuario ingresará al sistema. No puede repetirse. |
| **Tipo** | Sí | Seleccione si el usuario será **Empleado** o **Administrador**. |
| **Contraseña** | Sí | Contraseña inicial. Debe tener al menos 8 caracteres. |
| **Repetir contraseña** | Sí | Confirme la contraseña para evitar errores de tipeo. |

#### Campos adicionales para Empleados

Si selecciona el tipo **Empleado**, aparecen los siguientes campos:

| Campo | Descripción |
|---|---|
| **Área** | Define los permisos del empleado. Seleccione: Administración, Soporte o Atención al cliente. |
| **Cargo** | Descripción del puesto (informativo, no afecta permisos). |

#### Campos adicionales para Administradores

Si selecciona el tipo **Administrador**, el usuario creado tendrá por defecto acceso total. Sus permisos específicos son configurables por el administrador del sistema.

Una vez completado el formulario, haga clic en **Crear usuario**. El sistema crea el usuario y muestra un mensaje de confirmación. El nuevo usuario aparece en la tabla de la misma página.

Comunique al usuario su nombre de usuario y contraseña inicial en forma segura. Se recomienda que el usuario cambie su contraseña al iniciar sesión por primera vez.

### 6.3 Desactivar un usuario

Cuando un empleado deja de trabajar en la empresa o ya no debe tener acceso al sistema, su cuenta debe desactivarse. La desactivación no elimina al usuario ni su historial, solo le impide iniciar sesión.

En la tabla de usuarios, ubique la fila del usuario que desea desactivar y haga clic en **Desactivar** (texto en rojo al final de la fila).

El sistema mostrará un mensaje de confirmación: *"¿Desactivar este usuario?"*

- Haga clic en **Aceptar** para confirmar. El estado del usuario cambiará a **Inactivo**.
- Haga clic en **Cancelar** para volver sin realizar cambios.

> **Regla de seguridad:** Un administrador no puede desactivar su propia cuenta. El botón Desactivar no aparece en la fila del usuario actualmente conectado.

---

## Apéndice: Referencia de estados

### Estados de clientes

| Estado | Descripción |
|---|---|
| **Activo** | El cliente está operativo y puede tener servicios activos. |
| **Suspendido** | El cliente está temporalmente suspendido (puede reactivarse editando su estado). |
| **Baja** | El cliente fue dado de baja junto con todos sus servicios. |

### Estados de usuarios

| Estado | Descripción |
|---|---|
| **Activo** | El usuario puede iniciar sesión y operar el sistema. |
| **Inactivo** | El usuario no puede iniciar sesión. Sus datos se conservan en el sistema. |

---

*Villafañe Wifi — Sistema de Gestión Integral · Seminario de Integración 2026*
