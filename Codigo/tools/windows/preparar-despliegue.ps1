param(
    [string]$Destino = (Join-Path ([IO.Path]::GetTempPath()) 'villafane-wifi-despliegue.tar.gz')
)

$ErrorActionPreference = 'Stop'
$directorioAplicacion = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$manifest = Join-Path $directorioAplicacion 'public/build/manifest.json'
if (-not (Test-Path -LiteralPath $manifest)) {
    throw 'Falta compilar la interfaz con npm run build antes de preparar el despliegue.'
}

$rutas = @(
    'app', 'bootstrap', 'config', 'database/migrations', 'database/seeders',
    'public', 'resources', 'routes', 'infra', 'tools', 'artisan',
    'composer.json', 'composer.lock', '.env.production.example'
)

& tar -czf $Destino -C $directorioAplicacion `
    --exclude=bootstrap/cache/*.php --exclude=public/hot --exclude=public/storage @rutas
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudo crear el paquete de despliegue.'
}

$contenido = & tar -tzf $Destino
if ($LASTEXITCODE -ne 0 -or $contenido -match '(^|/)(\.env|vendor|storage|node_modules|\.git)(/|$)') {
    throw 'El paquete contiene rutas privadas o no se pudo verificar.'
}

$paquete = Get-Item -LiteralPath $Destino
$hash = Get-FileHash -LiteralPath $Destino -Algorithm SHA256
[pscustomobject]@{
    Paquete = $paquete.FullName
    Bytes = $paquete.Length
    SHA256 = $hash.Hash
} | ConvertTo-Json
