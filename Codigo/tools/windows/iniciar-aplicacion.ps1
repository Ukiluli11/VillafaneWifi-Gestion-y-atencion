$ErrorActionPreference = 'Stop'

& (Join-Path $PSScriptRoot 'iniciar-mariadb.ps1')

$ejecutablePhp = Join-Path $env:LOCALAPPDATA 'Programs\PHP-8.5\php.exe'
$directorioAplicacion = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path

if (-not (Test-Path -LiteralPath $ejecutablePhp)) {
    throw "No se encontro PHP en $ejecutablePhp. Revisa la instalacion local."
}

if (-not (Test-Path -LiteralPath (Join-Path $directorioAplicacion '.env'))) {
    throw 'Falta sistema\.env. Copia .env.example y completa la configuracion.'
}

Push-Location $directorioAplicacion

try {
    Write-Host 'Villafane Wifi estara disponible en http://127.0.0.1:8000'
    & $ejecutablePhp artisan serve --host=127.0.0.1 --port=8000
}
finally {
    Pop-Location
}
