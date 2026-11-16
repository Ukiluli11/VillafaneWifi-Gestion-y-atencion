param(
    [ValidateRange(1, 2147483647)]
    [int]$CuotaDemo = 26,
    [switch]$ProbarIA,
    [switch]$AjustarCupoDemo,
    [ValidateRange(1, 1441)]
    [int]$Muestras = 1,
    [ValidateRange(10, 3600)]
    [int]$IntervaloSegundos = 300
)

$ErrorActionPreference = 'Stop'
$servidor = 'root@201.32.131.114'
$salud = 'https://vps-6524456-x.dattaweb.com/up'
$opcionIA = [int]$ProbarIA.IsPresent
$opcionCupo = [int]$AjustarCupoDemo.IsPresent

$verificar = @'
set -Eeuo pipefail
cd /srv/villafane-wifi/current
if [[ "$3" == 1 ]]; then
    exec 8>/srv/villafane-wifi/.deploy.lock
    flock -n 8 || { echo 'Hay un despliegue en curso. Reintenta mas tarde.' >&2; exit 1; }

    env=/srv/villafane-wifi/shared/.env
    respaldo=$(mktemp "$env.cupo-anterior.XXXXXX")
    siguiente=$(mktemp "$env.cupo-nuevo.XXXXXX")
    trap 'rm -f "$respaldo" "$siguiente"' EXIT
    cp -p "$env" "$respaldo"
    cp -p "$env" "$siguiente"
    {
        awk '!/^[[:space:]]*(export[[:space:]]+)?WHATSAPP_LIMITE_PLANTILLAS_(MES|DIA)[[:space:]]*=/' "$env"
        printf '\nWHATSAPP_LIMITE_PLANTILLAS_MES=10\nWHATSAPP_LIMITE_PLANTILLAS_DIA=4\n'
    } > "$siguiente"
    mv -f "$siguiente" "$env"
    if ! runuser -u www-data -- php artisan config:cache --no-interaction || ! systemctl reload php8.5-fpm; then
        mv -f "$respaldo" "$env"
        runuser -u www-data -- php artisan config:cache --no-interaction
        systemctl reload php8.5-fpm
        echo 'Fallo el ajuste. Se restauro la configuracion anterior.' >&2
        exit 1
    fi
    flock -u 8
fi

echo "Version activa: $(pwd -P)"
echo "Programador de tareas: $(systemctl is-active villafane-scheduler.timer || true)"
runuser -u www-data -- php artisan schedule:list --no-interaction
runuser -u www-data -- php -- "$1" "$2" <<'PHP'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

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

$verificar | ssh.exe -T -o ConnectTimeout=10 -o NumberOfPasswordPrompts=1 -p 5644 $servidor "tr -d '\015' | bash -s -- $CuotaDemo $opcionIA $opcionCupo"
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
