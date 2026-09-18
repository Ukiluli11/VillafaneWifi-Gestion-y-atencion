$ErrorActionPreference = 'Stop'

$ejecutableMariaDb = Join-Path $env:LOCALAPPDATA 'Programs\mariadb-11.8.9-winx64\bin\mariadbd.exe'
$directorioDatos = Join-Path $env:LOCALAPPDATA 'VillafaneWifi\MariaDB\data'
$archivoRegistro = Join-Path $directorioDatos 'villafane-mariadb.log'

function Test-PuertoMariaDb {
    $cliente = [System.Net.Sockets.TcpClient]::new()

    try {
        $resultado = $cliente.BeginConnect('127.0.0.1', 3306, $null, $null)

        return $resultado.AsyncWaitHandle.WaitOne(250) -and $cliente.Connected
    }
    catch {
        return $false
    }
    finally {
        $cliente.Dispose()
    }
}

if (Test-PuertoMariaDb) {
    Write-Host 'MariaDB ya esta disponible en 127.0.0.1:3306.'

    exit 0
}

if (-not (Test-Path -LiteralPath $ejecutableMariaDb)) {
    throw "No se encontro MariaDB en $ejecutableMariaDb. Revisa la instalacion local."
}

if (-not (Test-Path -LiteralPath $directorioDatos)) {
    throw "No se encontro el directorio de datos en $directorioDatos."
}

$argumentos = @(
    "--datadir=$directorioDatos"
    '--port=3306'
    '--bind-address=127.0.0.1'
    "--log-error=$archivoRegistro"
)

Start-Process -FilePath $ejecutableMariaDb -ArgumentList $argumentos -WindowStyle Hidden

foreach ($intento in 1..20) {
    if (Test-PuertoMariaDb) {
        Write-Host 'MariaDB se inicio correctamente.'

        exit 0
    }

    Start-Sleep -Milliseconds 250
}

throw "MariaDB no pudo iniciarse. Revisa $archivoRegistro."
