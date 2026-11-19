# Revisa el estado del VPS y las condiciones necesarias para la demostración.
param(
    [ValidateRange(1, 2147483647)]
    [int]$CuotaDemo = 26,
    [switch]$ProbarIA,
    [switch]$VerificarAccesoWhatsapp,
    [switch]$AjustarCupoDemo,
    [switch]$ActivarFuncionesDemo,
    [ValidateRange(1, 1441)]
    [int]$Muestras = 1,
    [ValidateRange(10, 3600)]
    [int]$IntervaloSegundos = 300
)

$ErrorActionPreference = 'Stop'
$servidor = 'root@201.32.131.114'
$salud = 'https://vps-6524456-x.dattaweb.com/up'
$opcionIA = [int]$ProbarIA.IsPresent
$opcionCupo = [int]($AjustarCupoDemo.IsPresent -or $ActivarFuncionesDemo.IsPresent)
$opcionFunciones = [int]$ActivarFuncionesDemo.IsPresent
$opcionAcceso = [int]$VerificarAccesoWhatsapp.IsPresent

$verificar = @'
set -Eeuo pipefail
cd /srv/villafane-wifi/current
if [[ "$3" == 1 ]]; then
    exec 8>/srv/villafane-wifi/.deploy.lock
    flock -n 8 || { echo 'Hay un despliegue en curso. Reintenta mas tarde.' >&2; exit 1; }

    if [[ "$5" == 1 ]]; then
        command -v tesseract >/dev/null || { echo 'Falta Tesseract para activar OCR.' >&2; exit 1; }
        command -v pdftoppm >/dev/null || { echo 'Falta pdftoppm para leer PDF.' >&2; exit 1; }
        systemctl is-active --quiet villafane-ocr.service || { echo 'El trabajador OCR no esta activo.' >&2; exit 1; }
        runuser -u www-data -- php <<'PREVIA'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cuenta = \App\Models\CuentaReceptora::query()->find((int) config('services.transferencias.id_cuenta'));
if ($cuenta?->estado !== \App\Enums\EstadoCuentaReceptora::Activa || $cuenta->tipo->value !== 'mercado_pago') {
    file_put_contents('php://stderr', "Configura una cuenta receptora activa antes de activar todas las funciones.\n");
    exit(1);
}
if (blank(config('services.whatsapp.access_token')) || blank(config('services.whatsapp.phone_number_id'))) {
    file_put_contents('php://stderr', "Faltan las credenciales de WhatsApp.\n");
    exit(1);
}
PREVIA
    fi

    env=/srv/villafane-wifi/shared/.env
    respaldo=$(mktemp "$env.cupo-anterior.XXXXXX")
    siguiente=$(mktemp "$env.cupo-nuevo.XXXXXX")
    ajustes=$(mktemp)
    trap 'rm -f "$respaldo" "$siguiente" "$ajustes"' EXIT
    cp -p "$env" "$respaldo"
    cp -p "$env" "$siguiente"
    printf 'WHATSAPP_LIMITE_PLANTILLAS_MES=30\nWHATSAPP_LIMITE_PLANTILLAS_DIA=30\n' > "$ajustes"
    if [[ "$5" == 1 ]]; then
        cat >> "$ajustes" <<'AJUSTES'
TICKETS_NOTIFICAR_RESULTADO=true
COMPROBANTES_NOTIFICAR_RESULTADO=true
COMPROBANTES_PLANTILLAS_FUERA_VENTANA=true
OCR_HABILITADO=true
TRANSFERENCIAS_HABILITADAS=true
WHATSAPP_RECORDATORIOS_HABILITADOS=true
WHATSAPP_PLANTILLAS_REALES_HABILITADAS=true
WHATSAPP_CONTROL_CONSUMO_HABILITADO=true
WHATSAPP_LIMITE_SERVICIOS_MES=900
WHATSAPP_MODO_SIMULACION=false
WHATSAPP_VALIDAR_FIRMA=true
AJUSTES
    fi
    {
        awk -F= 'NR==FNR {claves[$1]=1; next} {
            clave=$1
            sub(/^[[:space:]]*(export[[:space:]]+)?/, "", clave)
            sub(/[[:space:]]+$/, "", clave)
            if (!(clave in claves)) print
        }' "$ajustes" "$env"
        printf '\n'
        cat "$ajustes"
    } > "$siguiente"
    mv -f "$siguiente" "$env"
    recargar_configuracion() {
        runuser -u www-data -- php artisan config:cache --no-interaction &&
        systemctl reload php8.5-fpm &&
        if [[ "$1" == 1 ]]; then
            systemctl restart villafane-ocr.service
        fi
    }
    if ! recargar_configuracion "$5"; then
        mv -f "$respaldo" "$env"
        recargar_configuracion "$5"
        echo 'Fallo el ajuste. Se restauro la configuracion anterior.' >&2
        exit 1
    fi
    flock -u 8
fi

echo "Version activa: $(pwd -P)"
echo "Trabajador OCR: $(systemctl is-active villafane-ocr.service || true)"
echo "Programador de tareas: $(systemctl is-active villafane-scheduler.timer || true)"
runuser -u www-data -- php artisan schedule:list --no-interaction
runuser -u www-data -- php -- "$1" "$2" "$4" <<'PHP'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if ($argv[3] === '1') {
    try {
        $token = (string) config('services.whatsapp.access_token');
        $version = (string) config('services.whatsapp.graph_api_version');
        $respuestaToken = \Illuminate\Support\Facades\Http::withToken($token)
            ->connectTimeout(3)->timeout(10)
            ->get("https://graph.facebook.com/{$version}/debug_token", ['input_token' => $token]);
        $datosToken = $respuestaToken->json('data');
        if (! $respuestaToken->successful() || ! is_array($datosToken)) {
            printf("Acceso WhatsApp: falta comprobar vencimiento en Meta (HTTP %d).\n", $respuestaToken->status());
        } else {
            printf("Acceso WhatsApp: %s\n", ($datosToken['is_valid'] ?? false) ? 'valido' : 'invalido');
            $finExposicion = \Carbon\CarbonImmutable::parse('2026-11-28 00:00:00', 'America/Argentina/Buenos_Aires')->timestamp;
            foreach (['expires_at' => 'Token', 'data_access_expires_at' => 'Acceso a datos'] as $campo => $titulo) {
                if (! array_key_exists($campo, $datosToken)) {
                    echo "{$titulo}: fecha pendiente de verificar en Meta.\n";
                } elseif ((int) $datosToken[$campo] === 0) {
                    echo "{$titulo}: Meta informa sin fecha de vencimiento.\n";
                } else {
                    $fecha = \Carbon\CarbonImmutable::createFromTimestamp((int) $datosToken[$campo])
                        ->setTimezone('America/Argentina/Buenos_Aires');
                    printf("%s: %s | %s\n", $titulo, $fecha->format('d/m/Y H:i:s'),
                        $fecha->timestamp >= $finExposicion ? 'cubre hasta el 27/11 inclusive' : 'requiere renovacion antes de la exposicion');
                }
            }
            printf("Permiso de mensajes: %s\n",
                in_array('whatsapp_business_messaging', $datosToken['scopes'] ?? [], true) ? 'presente' : 'revisar en Meta');
        }
    } catch (\Throwable) {
        echo "Acceso WhatsApp: no se pudo consultar a Meta. Reintentar la comprobacion.\n";
    }
}

foreach ([
    'tickets.notificar_resultado' => 'Avisos de tickets (ventana de 24 horas)',
    'comprobantes.notificar_resultado' => 'Avisos de comprobantes',
    'comprobantes.plantillas_fuera_ventana' => 'Plantillas de resultado fuera de 24 horas',
    'ocr.habilitado' => 'OCR',
    'transferencias.habilitadas' => 'Datos de transferencias',
    'whatsapp.recordatorios_habilitados' => 'Recordatorios por cliente',
] as $clave => $nombre) {
    printf("%s: %s\n", $nombre, config('services.'.$clave) ? 'activo' : 'inactivo');
}
printf("Clientes con recordatorios autorizados: %d\n", \App\Models\Cliente::query()->where('recordatorios_whatsapp', true)->count());

$consumo = app(\App\Dominio\ServicioConsumoWhatsapp::class)->resumir();
printf("Plantillas WhatsApp (UTC): %d/%d este mes | %d/%d hoy | control %s | envios reales %s\n",
    $consumo['plantillas'], $consumo['limite_plantillas'],
    $consumo['plantillas_hoy'], $consumo['limite_diario'],
    $consumo['control_habilitado'] ? 'activo' : 'inactivo',
    $consumo['plantillas_habilitadas'] && ! $consumo['simulacion'] ? 'habilitados' : 'bloqueados');

$id = (int) $argv[1];
printf("Hora argentina: %s\n", now('America/Argentina/Buenos_Aires')->format('d/m/Y H:i:s'));
$cuota = \App\Models\Cuota::query()->find($id);
$mensaje = \App\Models\Mensaje::query()
    ->where('clave_idempotencia', "vencimiento:vencida:{$id}")
    ->first();
printf("Cuota #%d: %s | periodo %s\n", $id, $cuota?->estado->value ?? 'no existe', $cuota?->periodo ?? '-');
if ($mensaje === null) {
    echo "Recordatorio vencido: sin registro de envio.\n";
} else {
    printf("Recordatorio vencido: %s | %s (hora argentina)\n",
        $mensaje->estado_envio->value,
        $mensaje->fecha_hora->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i:s'));
}

$ia = app(\App\Dominio\IAService::class);
$proveedor = method_exists($ia, 'proveedorActivo') ? $ia->proveedorActivo() : (
    ! config('services.whatsapp.modo_simulacion') && filled(config('services.openai.api_key'))
    && filled(config('services.openai.model')) ? 'openai' : null
);
if ($proveedor !== null) {
    printf("IA externa: %s | modelo %s\n", $proveedor, config("services.{$proveedor}.model"));
} else {
    echo "IA externa: pendiente de configuracion o en simulacion.\n";
}
if ($argv[2] !== '1' || $proveedor === null) {
    return;
}

$http = \Illuminate\Support\Facades\Http::getFacadeRoot();
$http->record();
$inicio = hrtime(true);
$resultado = $ia->interpretarIntencion('Quisiera saber cuanto me queda por abonar este mes.');
$segundos = (hrtime(true) - $inicio) / 1e9;
$respuesta = $http->recorded()->first()[1] ?? null;
if ($respuesta?->successful() && $resultado['intencion'] === \App\Enums\IntencionConversacion::ConsultaCuenta) {
    printf("IA real verificada: %s | %.2f s\n", $resultado['intencion']->value, $segundos);
} else {
    printf("IA real pendiente: HTTP %s | clasificacion %s | %.2f s\n",
        $respuesta?->status() ?? 'sin respuesta', $resultado['intencion']->value, $segundos);
}
PHP
'@

$verificar | ssh.exe -T -o ConnectTimeout=10 -o NumberOfPasswordPrompts=1 -p 5644 $servidor "tr -d '\015' | bash -s -- $CuotaDemo $opcionIA $opcionCupo $opcionAcceso $opcionFunciones"
if ($LASTEXITCODE -ne 0) {
    throw 'La verificacion SSH fallo. Revisar la salida anterior.'
}

$archivo = $null
if ($Muestras -gt 1) {
    $nombre = 'villafane-disponibilidad-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.csv'
    $archivo = Join-Path ([IO.Path]::GetTempPath()) $nombre
    'fecha_utc,codigo_http,segundos,salida_curl' | Set-Content -LiteralPath $archivo -Encoding ASCII
    Write-Host "Evidencia de disponibilidad: $archivo"
}

$correctas = 0
for ($muestra = 1; $muestra -le $Muestras; $muestra++) {
    $fecha = [DateTime]::UtcNow.ToString('o')
    $medicion = curl.exe --silent --max-time 10 --output NUL --write-out '%{http_code},%{time_total}' $salud
    $estadoCurl = $LASTEXITCODE
    $codigo, $segundos = ($medicion -split ',', 2)
    if ($estadoCurl -eq 0 -and $codigo -eq '200') { $correctas++ }

    Write-Host "Salud HTTPS: $fecha | HTTP $codigo | $segundos s | curl $estadoCurl"
    if ($archivo) { "$fecha,$codigo,$segundos,$estadoCurl" | Add-Content -LiteralPath $archivo -Encoding ASCII }
    if ($muestra -lt $Muestras) { Start-Sleep -Seconds $IntervaloSegundos }
}

if ($correctas -ne $Muestras) {
    throw "Respondieron HTTP 200 $correctas de $Muestras comprobaciones."
}
Write-Host "Respondieron HTTP 200 $correctas de $Muestras comprobaciones."
