param(
    [switch] $Validar
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$directorioAplicacion = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$directorioRepositorio = (Resolve-Path (Join-Path $directorioAplicacion '..')).Path
$ejecutablePhp = Join-Path $env:LOCALAPPDATA 'Programs\PHP-8.5\php.exe'
$comandoNode = Get-Command 'node.exe' -ErrorAction SilentlyContinue
$ejecutableNode = if ($null -ne $comandoNode) {
    $comandoNode.Source
}
else {
    @(
        (Join-Path $env:ProgramFiles 'nodejs\node.exe')
        (Join-Path $env:LOCALAPPDATA 'Programs\nodejs\node.exe')
        (Join-Path $env:USERPROFILE '.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe')
    ) | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1
}
$comandoPnpm = Get-Command 'pnpm.cmd' -ErrorAction SilentlyContinue
$ejecutablePnpm = if ($null -ne $comandoPnpm) {
    $comandoPnpm.Source
}
else {
    @(
        (Join-Path $env:APPDATA 'npm\pnpm.cmd')
        (Join-Path $env:USERPROFILE '.cache\codex-runtimes\codex-primary-runtime\dependencies\bin\fallback\pnpm.cmd')
    ) | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1
}
$archivoCertificados = @(
    'C:\Program Files\Git\mingw64\etc\ssl\certs\ca-bundle.crt'
    'C:\Program Files\Git\usr\ssl\certs\ca-bundle.crt'
    'C:\xampp\apache\bin\curl-ca-bundle.crt'
) | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1
$argumentosPhpSeguro = if ($archivoCertificados) {
    @('-d', "curl.cainfo=$archivoCertificados", '-d', "openssl.cafile=$archivoCertificados")
}
else {
    @()
}
$urlSistema = 'http://127.0.0.1:8000/iniciar-sesion'
$script:procesoAplicacion = $null
$script:procesoMariaDb = $null
$script:herramientasMariaDb = $null
$script:sistemaIniciado = $false
$script:cajaRegistro = $null
$script:etiquetaEstado = $null
$script:botonIniciar = $null
$script:botonAbrir = $null
$script:botonDetener = $null

function Test-Puerto {
    param(
        [Parameter(Mandatory)]
        [int] $Puerto
    )

    $cliente = [System.Net.Sockets.TcpClient]::new()

    try {
        $resultado = $cliente.BeginConnect('127.0.0.1', $Puerto, $null, $null)

        return $resultado.AsyncWaitHandle.WaitOne(300) -and $cliente.Connected
    }
    catch {
        return $false
    }
    finally {
        $cliente.Dispose()
    }
}

function Get-HerramientasMariaDb {
    $servidorLocal = Join-Path $env:LOCALAPPDATA 'Programs\mariadb-11.8.9-winx64\bin\mariadbd.exe'
    $clienteLocal = Join-Path $env:LOCALAPPDATA 'Programs\mariadb-11.8.9-winx64\bin\mariadb.exe'
    $datosLocales = Join-Path $env:LOCALAPPDATA 'VillafaneWifi\MariaDB\data'

    if ((Test-Path -LiteralPath $servidorLocal) -and (Test-Path -LiteralPath $clienteLocal) -and (Test-Path -LiteralPath $datosLocales)) {
        return [pscustomobject]@{
            Servidor = $servidorLocal
            Cliente = $clienteLocal
            Argumentos = @("--datadir=$datosLocales", '--port=3306', '--bind-address=127.0.0.1')
            Nombre = 'MariaDB 11.8'
        }
    }

    $servidorXampp = 'C:\xampp\mysql\bin\mysqld.exe'
    $clienteXampp = 'C:\xampp\mysql\bin\mysql.exe'
    $configuracionXampp = 'C:\xampp\mysql\bin\my.ini'

    if ((Test-Path -LiteralPath $servidorXampp) -and (Test-Path -LiteralPath $clienteXampp)) {
        return [pscustomobject]@{
            Servidor = $servidorXampp
            Cliente = $clienteXampp
            Argumentos = @("--defaults-file=$configuracionXampp", '--standalone')
            Nombre = 'MariaDB de XAMPP'
        }
    }

    throw 'No se encontro MariaDB. Instala MariaDB o XAMPP antes de iniciar el sistema.'
}

function Invoke-ClienteMariaDb {
    param(
        [Parameter(Mandatory)]
        [object] $Herramientas,
        [Parameter(Mandatory)]
        [string[]] $Argumentos
    )

    # MariaDB informa los fallos transitorios de handshake por stderr. El
    # launcher usa ErrorActionPreference=Stop, por lo que debemos capturarlos
    # aqui y devolver su codigo de salida para poder reintentar.
    $preferenciaAnterior = $ErrorActionPreference

    try {
        $ErrorActionPreference = 'Continue'
        $salida = & $Herramientas.Cliente @Argumentos 2>&1

        return [pscustomobject]@{
            CodigoSalida = $LASTEXITCODE
            Salida = ($salida -join ' ')
        }
    }
    finally {
        $ErrorActionPreference = $preferenciaAnterior
    }
}

function Set-VariableEntorno {
    param(
        [Parameter(Mandatory)]
        [string] $Ruta,
        [Parameter(Mandatory)]
        [string] $Nombre,
        [AllowEmptyString()]
        [string] $Valor
    )

    $contenido = [System.IO.File]::ReadAllText($Ruta)
    $linea = "$Nombre=$Valor"
    $patron = "(?m)^$([regex]::Escape($Nombre))=.*$"

    if ([regex]::IsMatch($contenido, $patron)) {
        $contenido = [regex]::Replace($contenido, $patron, $linea)
    }
    else {
        $contenido = $contenido.TrimEnd() + [Environment]::NewLine + $linea + [Environment]::NewLine
    }

    [System.IO.File]::WriteAllText($Ruta, $contenido, [System.Text.UTF8Encoding]::new($false))
}

function Initialize-ArchivoEntorno {
    $rutaEntorno = Join-Path $directorioAplicacion '.env'
    if (Test-Path -LiteralPath $rutaEntorno) {
        return
    }

    Copy-Item -LiteralPath (Join-Path $directorioAplicacion '.env.example') -Destination $rutaEntorno
    Set-VariableEntorno -Ruta $rutaEntorno -Nombre 'DB_CONNECTION' -Valor 'mariadb'
    Set-VariableEntorno -Ruta $rutaEntorno -Nombre 'DB_HOST' -Valor '127.0.0.1'
    Set-VariableEntorno -Ruta $rutaEntorno -Nombre 'DB_PORT' -Valor '3306'
    Set-VariableEntorno -Ruta $rutaEntorno -Nombre 'DB_DATABASE' -Valor 'villafane_wifi'
    Set-VariableEntorno -Ruta $rutaEntorno -Nombre 'DB_USERNAME' -Valor 'root'
    Set-VariableEntorno -Ruta $rutaEntorno -Nombre 'DB_PASSWORD' -Valor ''
    Set-VariableEntorno -Ruta $rutaEntorno -Nombre 'ADMIN_INICIAL_USUARIO' -Valor 'admin'
    Set-VariableEntorno -Ruta $rutaEntorno -Nombre 'ADMIN_INICIAL_CONTRASENA' -Valor 'Villafane2026!'
}

function Write-Registro {
    param(
        [Parameter(Mandatory)]
        [string] $Mensaje
    )

    if ($null -ne $script:cajaRegistro) {
        $hora = Get-Date -Format 'HH:mm:ss'
        $script:cajaRegistro.AppendText("[$hora] $Mensaje$([Environment]::NewLine)")
        $script:cajaRegistro.SelectionStart = $script:cajaRegistro.TextLength
        $script:cajaRegistro.ScrollToCaret()
        [System.Windows.Forms.Application]::DoEvents()
    }
}

function Set-Estado {
    param(
        [Parameter(Mandatory)]
        [string] $Texto,
        [Parameter(Mandatory)]
        [System.Drawing.Color] $Color
    )

    $script:etiquetaEstado.Text = $Texto
    $script:etiquetaEstado.ForeColor = $Color
    [System.Windows.Forms.Application]::DoEvents()
}

function Start-MariaDb {
    $herramientas = Get-HerramientasMariaDb
    $script:herramientasMariaDb = $herramientas

    if (-not (Test-Puerto -Puerto 3306)) {
        Write-Registro "Iniciando $($herramientas.Nombre)..."
        $parametrosServidor = @{
            FilePath = $herramientas.Servidor
            ArgumentList = $herramientas.Argumentos
            WindowStyle = 'Hidden'
            PassThru = $true
        }
        $script:procesoMariaDb = Start-Process @parametrosServidor

        foreach ($intento in 1..40) {
            if (Test-Puerto -Puerto 3306) {
                break
            }

            Start-Sleep -Milliseconds 250
            [System.Windows.Forms.Application]::DoEvents()
        }
    }

    if (-not (Test-Puerto -Puerto 3306)) {
        throw 'MariaDB no pudo iniciarse en el puerto 3306.'
    }

    # Que el puerto responda no garantiza que MariaDB haya terminado el
    # handshake. Esperamos una consulta real antes de invocar Laravel.
    $conexionLista = $false
    foreach ($intentoConexion in 1..40) {
        $argumentosPrueba = @(
            '--protocol=tcp',
            '--host=127.0.0.1',
            '--port=3306',
            '--user=root',
            '--connect-timeout=2',
            '--execute=SELECT 1;'
        )
        $resultadoPrueba = Invoke-ClienteMariaDb -Herramientas $herramientas -Argumentos $argumentosPrueba

        if ($resultadoPrueba.CodigoSalida -eq 0) {
            $conexionLista = $true
            break
        }

        Start-Sleep -Milliseconds 500
        [System.Windows.Forms.Application]::DoEvents()
    }

    if (-not $conexionLista) {
        throw 'MariaDB abrio el puerto 3306, pero no logro aceptar conexiones.'
    }

    Write-Registro 'MariaDB esta disponible.'
    $consulta = 'CREATE DATABASE IF NOT EXISTS villafane_wifi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
    $argumentosCliente = @(
        '--protocol=tcp',
        '--host=127.0.0.1',
        '--port=3306',
        '--user=root',
        '--connect-timeout=5',
        "--execute=$consulta"
    )
    $basePreparada = $false
    $salida = $null
    foreach ($intentoBase in 1..10) {
        $resultadoBase = Invoke-ClienteMariaDb -Herramientas $herramientas -Argumentos $argumentosCliente
        $salida = $resultadoBase.Salida

        if ($resultadoBase.CodigoSalida -eq 0) {
            $basePreparada = $true
            break
        }

        Write-Registro "MariaDB aun esta finalizando su inicio; esperando para preparar la base ($intentoBase/10)..."
        Start-Sleep -Seconds 1
        [System.Windows.Forms.Application]::DoEvents()
    }

    if (-not $basePreparada) {
        throw "No se pudo preparar la base villafane_wifi. $salida"
    }
}

function Invoke-ComandoAplicacion {
    param(
        [Parameter(Mandatory)]
        [string[]] $Argumentos,
        [Parameter(Mandatory)]
        [string] $Descripcion
    )

    Write-Registro $Descripcion
    $salida = & $ejecutablePhp @argumentosPhpSeguro @Argumentos 2>&1

    if ($LASTEXITCODE -ne 0) {
        throw "$Descripcion fallo. $($salida -join ' ')"
    }
}

function Build-Interfaz {
    if ([string]::IsNullOrWhiteSpace($ejecutableNode) -or -not (Test-Path -LiteralPath $ejecutableNode)) {
        throw 'No se encontro Node.js. Instala Node.js 20.19 o superior y vuelve a iniciar el sistema.'
    }

    if ([string]::IsNullOrWhiteSpace($ejecutablePnpm) -or -not (Test-Path -LiteralPath $ejecutablePnpm)) {
        Write-Registro 'No se encontro pnpm; se conservara la ultima compilacion disponible.'

        return
    }

    $rutaEntornoOriginal = $env:PATH
    $directorioNode = Split-Path -Parent $ejecutableNode
    $env:PATH = "$directorioNode;$rutaEntornoOriginal"
    Push-Location $directorioAplicacion

    try {
        $paqueteVite = Join-Path $directorioAplicacion 'node_modules\vite\package.json'
        # pnpm guarda las dependencias transitivas dentro de .pnpm; por eso
        # rolldown no necesariamente aparece en node_modules como carpeta directa.
        $directorioRolldown = Get-ChildItem -LiteralPath (Join-Path $directorioAplicacion 'node_modules\.pnpm') `
            -Directory -Filter 'rolldown@*' -ErrorAction SilentlyContinue | Select-Object -First 1
        $paqueteRolldown = if ($null -ne $directorioRolldown) {
            Join-Path $directorioRolldown.FullName 'node_modules\rolldown\package.json'
        }
        else {
            $null
        }

        if ((-not (Test-Path -LiteralPath (Join-Path $directorioAplicacion 'node_modules'))) -or
            (-not (Test-Path -LiteralPath $paqueteVite)) -or
            ([string]::IsNullOrWhiteSpace($paqueteRolldown)) -or
            (-not (Test-Path -LiteralPath $paqueteRolldown))) {
            Write-Registro 'Reparando dependencias de la interfaz...'
            & $ejecutablePnpm install --force --no-frozen-lockfile 2>&1 | Out-Null
            if ($LASTEXITCODE -ne 0) {
                throw 'No se pudieron instalar las dependencias de la interfaz.'
            }
        }

        Write-Registro 'Compilando la version local mas reciente...'
        $directorioRegistros = Join-Path $directorioAplicacion 'storage\logs'
        New-Item -ItemType Directory -Path $directorioRegistros -Force | Out-Null
        $archivoSalida = Join-Path $directorioRegistros 'launcher-build.log'
        $archivoError = Join-Path $directorioRegistros 'launcher-build-error.log'
        $procesoCompilacion = Start-Process -FilePath $ejecutablePnpm `
            -ArgumentList @('run', 'build') `
            -WorkingDirectory $directorioAplicacion `
            -RedirectStandardOutput $archivoSalida `
            -RedirectStandardError $archivoError `
            -WindowStyle Hidden `
            -Wait `
            -PassThru

        if ($procesoCompilacion.ExitCode -ne 0) {
            $detalle = Get-Content -LiteralPath $archivoError -Raw -ErrorAction SilentlyContinue
            throw "No se pudo compilar la interfaz. $detalle"
        }
    }
    finally {
        Pop-Location
        $env:PATH = $rutaEntornoOriginal
    }
}

function Start-Sistema {
    try {
        $script:botonIniciar.Enabled = $false
        Set-Estado -Texto 'INICIANDO...' -Color ([System.Drawing.Color]::DarkOrange)

        if (-not (Test-Path -LiteralPath $ejecutablePhp)) {
            throw "No se encontro PHP 8.5 en $ejecutablePhp."
        }

        $primeraPreparacion = -not (Test-Path -LiteralPath (Join-Path $directorioAplicacion '.env'))
        Initialize-ArchivoEntorno
        Start-MariaDb

        Push-Location $directorioAplicacion

        try {
            $rutaEntorno = Join-Path $directorioAplicacion '.env'
            $contenidoEntorno = Get-Content -LiteralPath $rutaEntorno -Raw
            if ($contenidoEntorno -match '(?m)^APP_KEY=$') {
            Invoke-ComandoAplicacion -Argumentos @('artisan', 'key:generate', '--force') -Descripcion 'Generando la clave de la aplicacion...'
            }

            Invoke-ComandoAplicacion -Argumentos @('artisan', 'config:clear') -Descripcion 'Actualizando la configuracion...'

            # XAMPP puede informar el puerto abierto unos instantes antes de
            # aceptar conexiones de PHP. Reintentamos para evitar un falso
            # error de inicio por una condicion transitoria de MariaDB.
            $migracionAplicada = $false
            foreach ($intentoMigracion in 1..3) {
                try {
                    Invoke-ComandoAplicacion -Argumentos @('artisan', 'migrate', '--force') -Descripcion 'Aplicando las migraciones pendientes...'
                    $migracionAplicada = $true
                    break
                }
                catch {
                    if ($intentoMigracion -eq 3) {
                        throw
                    }

                    Write-Registro "MariaDB aun se esta preparando; reintentando ($intentoMigracion/3)..."
                    Start-Sleep -Seconds 2
                    Start-MariaDb
                }
            }

            if (-not $migracionAplicada) {
                throw 'No se pudieron aplicar las migraciones.'
            }

            if ($primeraPreparacion) {
                Invoke-ComandoAplicacion -Argumentos @('artisan', 'db:seed', '--force') -Descripcion 'Cargando los datos de demostracion...'
            }

            Build-Interfaz
        }
        finally {
            Pop-Location
        }

        if (-not (Test-Puerto -Puerto 8000)) {
            $directorioRegistros = Join-Path $directorioAplicacion 'storage\logs'
            New-Item -ItemType Directory -Path $directorioRegistros -Force | Out-Null
            $parametrosAplicacion = @{
                FilePath = $ejecutablePhp
                # El servidor local no realiza solicitudes HTTPS; omitir aqui
                # curl.cainfo evita que una ruta con espacios (Program Files)
                # se divida incorrectamente al iniciar el proceso en Windows.
                ArgumentList = @('artisan', 'serve', '--host=127.0.0.1', '--port=8000')
                WorkingDirectory = $directorioAplicacion
                RedirectStandardOutput = (Join-Path $directorioRegistros 'launcher-laravel.log')
                RedirectStandardError = (Join-Path $directorioRegistros 'launcher-laravel-error.log')
                WindowStyle = 'Hidden'
                PassThru = $true
            }
            $script:procesoAplicacion = Start-Process @parametrosAplicacion

            foreach ($intento in 1..40) {
                if (Test-Puerto -Puerto 8000) {
                    break
                }

                Start-Sleep -Milliseconds 250
                [System.Windows.Forms.Application]::DoEvents()
            }
        }

        if (-not (Test-Puerto -Puerto 8000)) {
            throw 'Laravel no pudo iniciarse en el puerto 8000.'
        }

        $script:sistemaIniciado = $true
        $script:botonAbrir.Enabled = $true
        $script:botonDetener.Enabled = $true
        Set-Estado -Texto 'SISTEMA ENCENDIDO' -Color ([System.Drawing.Color]::SeaGreen)
        Write-Registro 'Sistema listo. Usuario: admin | Contrasena inicial: Villafane2026!'
        Start-Process $urlSistema
    }
    catch {
        $mensajeError = $_.Exception.Message
        Stop-Sistema
        Set-Estado -Texto 'NO SE PUDO INICIAR' -Color ([System.Drawing.Color]::Firebrick)
        Write-Registro $mensajeError
        [System.Windows.Forms.MessageBox]::Show(
            $mensajeError,
            'Villafane Wifi',
            [System.Windows.Forms.MessageBoxButtons]::OK,
            [System.Windows.Forms.MessageBoxIcon]::Error
        ) | Out-Null
    }
    finally {
        $script:botonIniciar.Enabled = -not $script:sistemaIniciado
    }
}

function Stop-Sistema {
    if ($null -ne $script:procesoAplicacion -and -not $script:procesoAplicacion.HasExited) {
        Stop-Process -Id $script:procesoAplicacion.Id -Force -ErrorAction SilentlyContinue
    }

    if ($null -ne $script:procesoMariaDb -and -not $script:procesoMariaDb.HasExited) {
        $directorioMariaDb = [System.IO.Path]::GetDirectoryName($script:herramientasMariaDb.Cliente)
        $administradorMariaDb = Join-Path $directorioMariaDb 'mysqladmin.exe'

        if (Test-Path -LiteralPath $administradorMariaDb) {
            & $administradorMariaDb --protocol=tcp --host=127.0.0.1 --port=3306 --user=root shutdown 2>&1 | Out-Null
            $script:procesoMariaDb.WaitForExit(5000)
        }

        if (-not $script:procesoMariaDb.HasExited) {
            Stop-Process -Id $script:procesoMariaDb.Id -Force -ErrorAction SilentlyContinue
        }
    }

    $script:procesoAplicacion = $null
    $script:procesoMariaDb = $null
    $script:herramientasMariaDb = $null

    $script:sistemaIniciado = $false
    $script:botonIniciar.Enabled = $true
    $script:botonAbrir.Enabled = $false
    $script:botonDetener.Enabled = $false
    Set-Estado -Texto 'SISTEMA APAGADO' -Color ([System.Drawing.Color]::DimGray)
    Write-Registro 'Los servicios iniciados por el launcher fueron detenidos.'
}

if ($Validar) {
    $herramientasMariaDb = $null

    try {
        $herramientasMariaDb = Get-HerramientasMariaDb
    }
    catch {
        # La validacion devuelve el estado sin abrir una ventana.
    }

    [pscustomobject]@{
        Repositorio = $directorioRepositorio
        Aplicacion = $directorioAplicacion
        Php = Test-Path -LiteralPath $ejecutablePhp
        MariaDb = $null -ne $herramientasMariaDb
        Node = (-not [string]::IsNullOrWhiteSpace($ejecutableNode)) -and (Test-Path -LiteralPath $ejecutableNode)
        Pnpm = (-not [string]::IsNullOrWhiteSpace($ejecutablePnpm)) -and (Test-Path -LiteralPath $ejecutablePnpm)
        LauncherValido = (Test-Path -LiteralPath $ejecutablePhp) -and ($null -ne $herramientasMariaDb)
    } | ConvertTo-Json

    exit
}

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing
[System.Windows.Forms.Application]::EnableVisualStyles()

$ventana = [System.Windows.Forms.Form]::new()
$ventana.Text = 'Villafane Wifi - Launcher'
$ventana.StartPosition = 'CenterScreen'
$ventana.ClientSize = [System.Drawing.Size]::new(640, 470)
$ventana.MinimumSize = [System.Drawing.Size]::new(600, 430)
$ventana.BackColor = [System.Drawing.Color]::FromArgb(242, 248, 245)
$ventana.Font = [System.Drawing.Font]::new('Segoe UI', 10)

$titulo = [System.Windows.Forms.Label]::new()
$titulo.Text = 'Sistema Villafane Wifi'
$titulo.Font = [System.Drawing.Font]::new('Segoe UI Semibold', 20)
$titulo.ForeColor = [System.Drawing.Color]::FromArgb(18, 80, 62)
$titulo.AutoSize = $true
$titulo.Location = [System.Drawing.Point]::new(28, 24)
$ventana.Controls.Add($titulo)

$subtitulo = [System.Windows.Forms.Label]::new()
$subtitulo.Text = 'Inicia la base de datos, actualiza el sistema y abre el panel.'
$subtitulo.ForeColor = [System.Drawing.Color]::DimGray
$subtitulo.AutoSize = $true
$subtitulo.Location = [System.Drawing.Point]::new(31, 66)
$ventana.Controls.Add($subtitulo)

$script:etiquetaEstado = [System.Windows.Forms.Label]::new()
$script:etiquetaEstado.Text = 'SISTEMA APAGADO'
$script:etiquetaEstado.Font = [System.Drawing.Font]::new('Segoe UI Semibold', 11)
$script:etiquetaEstado.ForeColor = [System.Drawing.Color]::DimGray
$script:etiquetaEstado.AutoSize = $true
$script:etiquetaEstado.Location = [System.Drawing.Point]::new(31, 103)
$ventana.Controls.Add($script:etiquetaEstado)

$script:botonIniciar = [System.Windows.Forms.Button]::new()
$script:botonIniciar.Text = 'INICIAR TODO'
$script:botonIniciar.Size = [System.Drawing.Size]::new(170, 46)
$script:botonIniciar.Location = [System.Drawing.Point]::new(30, 140)
$script:botonIniciar.BackColor = [System.Drawing.Color]::FromArgb(25, 133, 94)
$script:botonIniciar.ForeColor = [System.Drawing.Color]::White
$script:botonIniciar.FlatStyle = 'Flat'
$script:botonIniciar.FlatAppearance.BorderSize = 0
$script:botonIniciar.Add_Click({ Start-Sistema })
$ventana.Controls.Add($script:botonIniciar)

$script:botonAbrir = [System.Windows.Forms.Button]::new()
$script:botonAbrir.Text = 'ABRIR SISTEMA'
$script:botonAbrir.Size = [System.Drawing.Size]::new(170, 46)
$script:botonAbrir.Location = [System.Drawing.Point]::new(215, 140)
$script:botonAbrir.Enabled = $false
$script:botonAbrir.Add_Click({ Start-Process $urlSistema })
$ventana.Controls.Add($script:botonAbrir)

$script:botonDetener = [System.Windows.Forms.Button]::new()
$script:botonDetener.Text = 'APAGAR'
$script:botonDetener.Size = [System.Drawing.Size]::new(120, 46)
$script:botonDetener.Location = [System.Drawing.Point]::new(400, 140)
$script:botonDetener.Enabled = $false
$script:botonDetener.Add_Click({ Stop-Sistema })
$ventana.Controls.Add($script:botonDetener)

$script:cajaRegistro = [System.Windows.Forms.TextBox]::new()
$script:cajaRegistro.Multiline = $true
$script:cajaRegistro.ReadOnly = $true
$script:cajaRegistro.ScrollBars = 'Vertical'
$script:cajaRegistro.BackColor = [System.Drawing.Color]::White
$script:cajaRegistro.ForeColor = [System.Drawing.Color]::FromArgb(48, 65, 59)
$script:cajaRegistro.Location = [System.Drawing.Point]::new(30, 210)
$script:cajaRegistro.Size = [System.Drawing.Size]::new(580, 210)
$script:cajaRegistro.Anchor = 'Top, Bottom, Left, Right'
$ventana.Controls.Add($script:cajaRegistro)

$nota = [System.Windows.Forms.Label]::new()
$nota.Text = 'El launcher usa la version local actual y no descarga cambios de GitHub.'
$nota.ForeColor = [System.Drawing.Color]::Gray
$nota.AutoSize = $true
$nota.Location = [System.Drawing.Point]::new(31, 435)
$nota.Anchor = 'Bottom, Left'
$ventana.Controls.Add($nota)

$ventana.Add_FormClosing({
    if ($null -ne $script:procesoAplicacion -or $null -ne $script:procesoMariaDb) {
        Stop-Sistema
    }
})

Write-Registro "Proyecto: $directorioRepositorio"
[void] $ventana.ShowDialog()
