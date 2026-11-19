# Prepara los servicios necesarios y muestra los datos para iniciar una demostración local.
$ErrorActionPreference = 'Stop'

$aplicacion = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$php = (Get-Command php -CommandType Application).Source
$demo = Join-Path ([IO.Path]::GetTempPath()) ('villafane-resumen-' + [guid]::NewGuid().ToString('N'))
$vistas = Join-Path $demo 'views'
New-Item -ItemType Directory -Path $vistas -Force | Out-Null

$base = Join-Path $demo 'demo.sqlite'
[IO.File]::WriteAllBytes($base, [byte[]]@())
$clave = & $php -r 'echo base64_encode(random_bytes(32));'
if ($LASTEXITCODE -ne 0) { throw 'No se pudo generar la clave local de la aplicacion.' }

$ajustes = @{
    APP_ENV = 'local'
    APP_DEBUG = 'false'
    APP_URL = 'http://127.0.0.1:8077'
    APP_KEY = "base64:$clave"
    APP_CONFIG_CACHE = (Join-Path $demo 'config.php')
    APP_ROUTES_CACHE = (Join-Path $demo 'routes.php')
    VIEW_COMPILED_PATH = $vistas
    LARAVEL_STORAGE_PATH = $demo
    DB_CONNECTION = 'sqlite'
    DB_DATABASE = $base
    CACHE_STORE = 'array'
    SESSION_DRIVER = 'cookie'
    SESSION_DOMAIN = 'null'
    SESSION_SECURE_COOKIE = 'false'
    SESSION_COOKIE = 'villafane_resumen_demo'
    LOG_CHANNEL = 'stderr'
    QUEUE_CONNECTION = 'sync'
    WHATSAPP_MODO_SIMULACION = 'true'
    WHATSAPP_ACCESS_TOKEN = 'null'
    WHATSAPP_RECORDATORIOS_HABILITADOS = 'false'
    WHATSAPP_PLANTILLAS_REALES_HABILITADAS = 'false'
    GEMINI_API_KEY = 'null'
    OPENAI_API_KEY = 'null'
    OCR_HABILITADO = 'false'
    ADMIN_INICIAL_USUARIO = 'vista-demo'
    ADMIN_INICIAL_CONTRASENA = 'ResumenLocal!8077'
}
$anteriores = @{}

Push-Location $aplicacion
try {
    foreach ($nombre in $ajustes.Keys) {
        $anteriores[$nombre] = [Environment]::GetEnvironmentVariable($nombre, 'Process')
        [Environment]::SetEnvironmentVariable($nombre, $ajustes[$nombre], 'Process')
    }

    & $php artisan migrate --seed --force --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo preparar la base de demostracion.' }

    $router = (Resolve-Path 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php').Path
    Set-Location (Join-Path $aplicacion 'public')
    Write-Host 'Resumen local: http://127.0.0.1:8077/panel'
    Write-Host 'Usuario: vista-demo | Clave: ResumenLocal!8077'
    Write-Host 'Deja esta ventana abierta mientras usas la demo. Para cerrarla, presiona Ctrl+C.'
    & $php -S 127.0.0.1:8077 $router
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo mantener abierto el servidor local.' }
}
finally {
    Pop-Location
    foreach ($nombre in $anteriores.Keys) {
        [Environment]::SetEnvironmentVariable($nombre, $anteriores[$nombre], 'Process')
    }
}
