# Ejecuta las pruebas de escalabilidad contra una instancia temporal de MariaDB.
param(
    [ValidateRange(1024, 65535)]
    [int]$Puerto = 43317
)

$ErrorActionPreference = 'Stop'
$aplicacion = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$instalador = (Get-Command mariadb-install-db.exe -ErrorAction Stop).Source
$ejecutable = (Get-Command mariadbd.exe -ErrorAction Stop).Source
$cliente = (Get-Command mariadb.exe -ErrorAction Stop).Source

$id = [guid]::NewGuid().ToString('N')
$temporal = Join-Path ([IO.Path]::GetTempPath()) "villafane-qa-$id"
$datos = Join-Path $temporal 'datos'
$registro = Join-Path $temporal 'mariadb.log'
$base = 'villafane_qa_' + $id
$clave = [guid]::NewGuid().ToString('N')
$proceso = $null

New-Item -ItemType Directory -Path $temporal | Out-Null
try {
    & $instalador "--datadir=$datos" "--port=$Puerto" "--password=$clave" --silent
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo inicializar MariaDB temporal.' }

    $opcionesCliente = Join-Path $temporal 'cliente.cnf'
    [IO.File]::WriteAllLines($opcionesCliente, @('[client]', "password=$clave"), [Text.Encoding]::ASCII)

    $argumentos = @(
        '--no-defaults'
        "--datadir=""$datos"""
        "--port=$Puerto"
        '--bind-address=127.0.0.1'
        "--log-error=""$registro"""
    )
    $proceso = Start-Process -FilePath $ejecutable -ArgumentList $argumentos -WindowStyle Hidden -PassThru
    $conexion = @("--defaults-file=$opcionesCliente", '--protocol=tcp', '--host=127.0.0.1', "--port=$Puerto", '--user=root', '--connect-timeout=2', '--skip-ssl')
    $disponible = $false
    foreach ($intento in 1..20) {
        if ($proceso.HasExited) { break }
        $codigoConexion = & {
            $ErrorActionPreference = 'Continue'
            & $cliente @conexion --execute='SELECT 1;' *> $null
            $LASTEXITCODE
        }
        if ($codigoConexion -eq 0) { $disponible = $true; break }
        Start-Sleep -Milliseconds 250
    }
    if (-not $disponible -or $proceso.HasExited) {
        if (Test-Path -LiteralPath $registro) { Get-Content -LiteralPath $registro -Tail 8 }
        if (-not $proceso.HasExited) {
            & {
                $ErrorActionPreference = 'Continue'
                & $cliente @conexion --execute='SELECT 1;'
            }
        }
        throw "MariaDB temporal no quedo disponible en 127.0.0.1:$Puerto."
    }

    & $cliente @conexion --execute="CREATE DATABASE $base CHARACTER SET utf8mb4; SELECT VERSION() AS version_mariadb;"
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear la base exclusiva de la prueba.' }

    [xml]$config = [IO.File]::ReadAllText((Join-Path $aplicacion 'phpunit.xml'))
    $config.phpunit.RemoveAttribute('bootstrap')
    $config.phpunit.RemoveAttribute('noNamespaceSchemaLocation', 'http://www.w3.org/2001/XMLSchema-instance')
    $config.phpunit.RemoveChild($config.phpunit.testsuites) | Out-Null
    $config.SelectSingleNode('/phpunit/source/include/directory').InnerText = Join-Path $aplicacion 'app'
    $variables = @{
        APP_ENV = 'testing'
        APP_CONFIG_CACHE = Join-Path $temporal 'config-inexistente.php'
        DB_CONNECTION = 'mariadb'
        DB_DATABASE = $base
        DB_HOST = '127.0.0.1'
        DB_PORT = [string]$Puerto
        DB_USERNAME = 'root'
        DB_PASSWORD = $clave
        DB_URL = ''
        DB_SOCKET = ''
    }
    foreach ($nombre in $variables.Keys) {
        $nodo = $config.SelectSingleNode("/phpunit/php/env[@name='$nombre']")
        if ($null -eq $nodo) {
            $nodo = $config.CreateElement('env')
            $nodo.SetAttribute('name', $nombre)
            $config.phpunit.php.AppendChild($nodo) | Out-Null
        }
        $nodo.SetAttribute('value', $variables[$nombre])
        $nodo.SetAttribute('force', 'true')
    }
    $configPath = Join-Path $temporal 'phpunit.xml'
    $config.Save($configPath)

    Push-Location -LiteralPath $aplicacion
    try {
        php vendor/bin/phpunit --configuration $configPath --do-not-cache-result 'tests/Performance/EscalabilidadTest.php'
        if ($LASTEXITCODE -ne 0) { throw 'La prueba de carga fallo.' }
    }
    finally {
        Pop-Location
    }
}
finally {
    if ($null -ne $proceso -and -not $proceso.HasExited) {
        Stop-Process -InputObject $proceso -ErrorAction SilentlyContinue
        $proceso.WaitForExit(5000) | Out-Null
    }
    $resuelto = (Resolve-Path -LiteralPath $temporal).Path
    if ($resuelto -eq $temporal -and (Split-Path -Leaf $resuelto) -eq "villafane-qa-$id") {
        Remove-Item -LiteralPath $resuelto -Recurse -Force
    }
}
