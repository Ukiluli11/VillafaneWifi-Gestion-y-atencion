# Sistema de Gestión y Atención — Villafañe Wifi

Proyecto desarrollado para la materia **Seminario de Integración** de la
Licenciatura en Sistemas de Información. El sistema centraliza la administración
de clientes, planes, conexiones, cuenta corriente y atención mediante WhatsApp.

## Estado actual

Actualmente se encuentran implementadas las siguientes funciones:

- Autenticación y permisos para administradores y empleados.
- Alta, consulta, modificación y baja lógica de clientes.
- Administración de planes y servicios de internet.
- Generación de cuotas, cuenta corriente y registro de pagos.
- Administración de cuentas receptoras.
- Panel de conversaciones y mensajes de WhatsApp.
- Identificación del cliente mediante su número de WhatsApp.
- Menú automático y consulta del estado de cuenta.
- Recepción y bandeja de comprobantes pendientes de validación.
- API REST versionada en `/api/v1`.
- Tema claro/oscuro y barra lateral adaptable.

El bot puede probarse mediante eventos simulados sin contratar todavía un
número de WhatsApp Business. Para conectarlo con WhatsApp real deben
configurarse las credenciales de Meta Cloud API.

## Tecnologías

| Componente | Tecnología |
|---|---|
| Backend | PHP 8.3 o superior y Laravel 13 |
| Arquitectura | Monolito modular orientado a objetos |
| Acceso a datos | Eloquent ORM y migraciones Laravel |
| Base de datos | MariaDB 11.8 |
| Interfaz web | Blade, HTML, CSS y JavaScript |
| Recursos frontend | Vite 8 y Tailwind CSS 4 |
| Mensajería | WhatsApp Business Platform (Meta Cloud API) |
| Pruebas | PHPUnit 12 |

## Requisitos previos

Para ejecutar el proyecto en otra computadora se necesita:

- Git.
- PHP 8.3 o superior, con las extensiones `ctype`, `curl`, `fileinfo`,
  `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer` y `xml`.
- Composer 2.
- MariaDB 11.8 o una versión compatible.
- Node.js 20.19 o superior y npm.

Las dependencias PHP se declaran en `Codificación del Sistema/composer.json` y
las dependencias del frontend en `Codificación del Sistema/package.json`. Las
carpetas `vendor` y `node_modules`
no se guardan en Git: se reconstruyen durante la instalación.

## Inicio rápido con el launcher

Desde la raíz del repositorio, hacé doble clic en el archivo:

```text
Iniciar Villafane Wifi.bat
```

El launcher prepara la configuración local, inicia MariaDB, aplica las
migraciones, carga los datos de demostración, compila la interfaz y abre el
panel. También puede iniciarse directamente con PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -STA -File "Codificación del Sistema\tools\windows\launcher-villafane.ps1"
```

El panel queda disponible en `http://127.0.0.1:8000/iniciar-sesion`.
La primera preparación crea el usuario `admin` con la contraseña local
`Villafane2026!`; debe cambiarse antes de cualquier despliegue real.

## Instalación manual

### 1. Clonar el repositorio

```powershell
git clone https://github.com/Ukiluli11/VillafaneWifi-Gestion-y-atencion.git
Set-Location VillafaneWifi-Gestion-y-atencion
git switch modulo-2-conversaciones
Set-Location 'Codificación del Sistema'
```

### 2. Instalar las dependencias

```powershell
composer install
npm install
```

### 3. Crear la configuración local

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

El archivo `.env` contiene datos locales y secretos, por lo que no debe
subirse al repositorio.

### 4. Crear la base de datos

Desde MariaDB se puede preparar una base y un usuario de desarrollo con:

```sql
CREATE DATABASE villafane_wifi
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'villafane_app'@'localhost' IDENTIFIED BY 'cambiar_esta_clave';
GRANT ALL PRIVILEGES ON villafane_wifi.* TO 'villafane_app'@'localhost';
FLUSH PRIVILEGES;
```

Luego se deben completar en `.env` los valores correspondientes:

```dotenv
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=villafane_wifi
DB_USERNAME=villafane_app
DB_PASSWORD=cambiar_esta_clave
```

### 5. Definir el administrador inicial

Antes de cargar la base, establecer en `.env`:

```dotenv
ADMIN_INICIAL_USUARIO=admin
ADMIN_INICIAL_CONTRASENA=una_clave_segura
```

### 6. Crear las tablas y cargar ejemplos

```powershell
php artisan migrate --seed
```

La carga demostrativa incluye clientes, planes, servicios, cuotas, pagos,
conversaciones, mensajes y comprobantes. Puede ejecutarse nuevamente sin
duplicar los ejemplos principales.

### 7. Compilar los recursos visuales

```powershell
npm run build
```

### 8. Ejecutar el sistema

```powershell
php artisan serve
```

El panel estará disponible en:

```text
http://127.0.0.1:8000/iniciar-sesion
```

Durante el desarrollo del frontend también puede ejecutarse `npm run dev` en
otra terminal.

## Configuración de WhatsApp

La conexión real con Meta requiere completar estas variables de `.env`:

```dotenv
WHATSAPP_URL_BASE=https://graph.facebook.com
WHATSAPP_MODO_SIMULACION=true
WHATSAPP_DESCARGAR_ARCHIVOS=true
WHATSAPP_TAMANO_MAXIMO_ARCHIVO_KB=10240
WHATSAPP_TOKEN_VERIFICACION=
WHATSAPP_SECRETO_APLICACION=
WHATSAPP_TOKEN_ACCESO=
WHATSAPP_ID_NUMERO_TELEFONO=
WHATSAPP_VERSION_API=v25.0
WHATSAPP_CERTIFICADO_CA=
```

Mientras estas credenciales no estén configuradas, las pruebas automatizadas y
los datos demostrativos permiten revisar el funcionamiento sin enviar mensajes
reales ni generar cargos de Meta.

## Pruebas y calidad de código

Ejecutar todas las pruebas:

```powershell
php artisan test
```

Aplicar el formato establecido para PHP:

```powershell
vendor\bin\pint
```

En el estado actual, el proyecto posee **81 pruebas automatizadas** con
**442 verificaciones**.

## Organización del repositorio

```text
VillafaneWifi-Gestion-y-atencion/
├── Codificación del Sistema/        Aplicación Laravel ejecutable
│   ├── app/Dominio/               Reglas y servicios del negocio
│   ├── app/Models/                Entidades Eloquent
│   ├── app/Autorizacion/          Permisos orientados a objetos
│   ├── app/Http/Controllers/      Controladores web y API
│   ├── database/migrations/       Definición reproducible de tablas
│   ├── database/seeders/          Datos iniciales y demostrativos
│   ├── resources/views/           Pantallas Blade
│   └── tests/                     Pruebas unitarias y funcionales
└── Documentación del Sistema/
    ├── Documentación/             Informes, entrevistas y manuales
    └── Diagramas/                 DER, UML y cronograma de Gantt
```

## Documentación relacionada

- `Documentación del Sistema/Documentación/Informe Del Sistema.docx`.
- `Documentación del Sistema/Documentación/Manuales/` para los manuales de usuario.
- `Documentación del Sistema/Documentación/Entrevistas/` para el relevamiento.
- `Documentación del Sistema/Diagramas/` para los diagramas y el cronograma.

## Integrantes

- Agustín Belazquez.
- Ulises Serrano.

Licenciatura en Sistemas de Información — Seminario de Integración, 2026.
