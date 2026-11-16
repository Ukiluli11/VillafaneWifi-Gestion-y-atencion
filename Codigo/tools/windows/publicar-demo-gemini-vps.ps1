param(
    [ValidatePattern('^[0-9]{8,20}(,[0-9]{8,20})*$')]
    [string]$TelefonosDemo = '5493704375104',
    [ValidatePattern('^villafane-gemini-[a-f0-9]{32}\.tar\.gz$')]
    [string]$PaqueteSubido,
    [switch]$ClaveDesdePortapapeles,
    [switch]$ActualizarDemoActiva
)

$ErrorActionPreference = 'Stop'
$clave = ''
if ($ActualizarDemoActiva) {
    Write-Host 'Se publicara el codigo con la configuracion de Gemini que ya existe en el VPS.'
} elseif ($ClaveDesdePortapapeles) {
    Read-Host 'Copia la clave en Google AI Studio, volve a esta ventana y presiona Enter sin pegarla' | Out-Null
    $clave = ([string](Get-Clipboard -Raw)).Trim()
} else {
    $secreta = Read-Host 'Pega la clave de Gemini' -AsSecureString
    $puntero = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secreta)
    try {
        $clave = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($puntero).Trim()
    }
    finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($puntero)
        $secreta.Dispose()
    }
}
if (-not $ActualizarDemoActiva -and $clave -notmatch '^(?:AQ\.)?[A-Za-z0-9_-]{20,}$') {
    $longitud = $clave.Length
    $clave = $null
    throw "Se leyeron $longitud caracteres, pero el formato no coincide con una clave de API. Usa el boton Copiar de la clave completa en Google AI Studio."
}

$servidor = 'root@201.32.131.114'
$nombre = $PaqueteSubido
if (-not $nombre) {
    $nombre = 'villafane-gemini-' + [guid]::NewGuid().ToString('N') + '.tar.gz'
    $paquete = Join-Path ([IO.Path]::GetTempPath()) $nombre
    & (Join-Path $PSScriptRoot 'preparar-despliegue.ps1') -Destino $paquete
    scp.exe -o NumberOfPasswordPrompts=1 -P 5644 $paquete ($servidor + ':/root/' + $nombre)
    if ($LASTEXITCODE -ne 0) { throw 'Fallo la subida del paquete.' }
}

$remoto = @'
set -Eeuo pipefail
umask 077
paquete=$1
telefonos=$2
actualizar=$3
respuesta=
[[ -f "$paquete" ]] || { echo 'No se encontro el paquete en el VPS.' >&2; exit 1; }
if [[ "$actualizar" != 1 ]]; then
IFS= read -r clave
clave="${clave%$'\r'}"
[[ "$clave" =~ ^(AQ\.)?[A-Za-z0-9_-]{20,}$ ]] || { echo 'El VPS no recibio una clave valida por la entrada privada.' >&2; exit 1; }

respuesta=$(mktemp)
trap 'rm -f "$respuesta"' EXIT
api=https://generativelanguage.googleapis.com/v1beta/models
consultar_gemini() {
    printf 'header = "x-goog-api-key: %s"\n' "$clave" | curl --config - \
        --silent --show-error --max-time 15 --output "$respuesta" --write-out '%{http_code}' "$@"
}

codigo=$(consultar_gemini "$api?pageSize=1000")
[[ "$codigo" == 200 ]] || { echo "Gemini rechazo la consulta de modelos (HTTP $codigo)." >&2; exit 1; }
catalogo=$(php -r '
    $datos = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
    foreach ($datos["models"] ?? [] as $modelo) {
        if (in_array("generateContent", $modelo["supportedGenerationMethods"] ?? [], true)) {
            echo $modelo["name"], PHP_EOL;
        }
    }
' "$respuesta")

modelo=
for candidato in gemini-3.1-flash-lite gemini-3.5-flash-lite; do
    if ! grep -Fxq "models/$candidato" <<< "$catalogo"; then
        echo "$candidato no figura en el catalogo de esta clave."
        continue
    fi
    codigo=$(consultar_gemini --header 'Content-Type: application/json' \
        --data '{"systemInstruction":{"parts":[{"text":"Clasifica consultas de saldo como consulta_cuenta. Usa confianza entre cero y uno y pregunta vacia para una intencion clara."}]},"contents":[{"parts":[{"text":"Quisiera saber cuanto me queda por abonar este mes."}]}],"generationConfig":{"maxOutputTokens":200,"responseMimeType":"application/json","responseJsonSchema":{"type":"object","properties":{"intencion":{"type":"string","enum":["consulta_cuenta","desconocida"]},"confianza":{"type":"number","minimum":0,"maximum":1},"pregunta":{"type":"string"}},"required":["intencion","confianza","pregunta"],"additionalProperties":false}}}' \
        "$api/$candidato:generateContent")
    if [[ "$codigo" == 200 ]] && php -r '
        $datos = json_decode(file_get_contents($argv[1]), true);
        foreach ($datos["candidates"][0]["content"]["parts"] ?? [] as $parte) {
            if (($parte["thought"] ?? false) || ! is_string($parte["text"] ?? null)) {
                continue;
            }
            $salida = json_decode($parte["text"], true);
            $confianza = $salida["confianza"] ?? null;
            if (($salida["intencion"] ?? null) === "consulta_cuenta"
                && (is_int($confianza) || is_float($confianza)) && $confianza >= 0.65 && $confianza <= 1) {
                exit(0);
            }
        }
        exit(1);
    ' "$respuesta"; then
        modelo=$candidato
        break
    fi
    echo "$candidato no paso la prueba de clasificacion (HTTP $codigo)."
done
[[ -n "$modelo" ]] || { echo 'Ningun modelo Flash-Lite paso la prueba. Se mantuvo el despliegue actual.' >&2; exit 1; }
echo "Clave y modelo aceptados por Gemini: $modelo."
fi

config=$(nginx -T 2>/dev/null)
fpm=$(printf '%s\n' "$config" | awk '
    /^# configuration file / { if (sitio) printf "%s", sockets; sitio=0; sockets="" }
    /root[[:space:]]+\/srv\/villafane-wifi\/current\/public;/ { sitio=1 }
    /fastcgi_pass[[:space:]]+unix:.*php8\.[3-9]-fpm\.sock;/ {
        match($0, /php8\.[3-9]-fpm/)
        sockets=sockets substr($0, RSTART, RLENGTH) "\n"
    }
    END { if (sitio) printf "%s", sockets }
' | sort -u)
[[ "$fpm" =~ ^php8\.[3-9]-fpm$ ]] || { echo 'No se pudo identificar PHP-FPM en Nginx.' >&2; exit 1; }

script=$(mktemp)
trap 'rm -f "$respuesta" "$script"' EXIT
tar -xOzf "$paquete" tools/desplegar.sh > "$script"
bash "$script" "$paquete" "$fpm"
if [[ "$actualizar" == 1 ]]; then
    echo 'Actualizacion publicada con la configuracion de Gemini existente.'
    exit 0
fi

env=/srv/villafane-wifi/shared/.env
anterior=$(mktemp /srv/villafane-wifi/shared/.env-gemini-anterior.XXXXXX)
siguiente=$(mktemp /srv/villafane-wifi/shared/.env-gemini-siguiente.XXXXXX)
trap 'rm -f "$respuesta" "$script" "$anterior" "$siguiente"' EXIT

cd /srv/villafane-wifi/current
cp --preserve=mode,ownership "$env" "$anterior"
cp --preserve=mode,ownership "$env" "$siguiente"
{
    awk '!/^[[:space:]]*(export[[:space:]]+)?(IA_PROVEEDOR|GEMINI_API_KEY|GEMINI_MODEL|IA_DESTINATARIOS_DEMO)[[:space:]]*=/' "$env"
    printf '\nIA_PROVEEDOR=gemini\nGEMINI_MODEL=%s\nGEMINI_API_KEY=%s\nIA_DESTINATARIOS_DEMO=%s\n' "$modelo" "$clave" "$telefonos"
} > "$siguiente"
unset clave
mv -f "$siguiente" "$env"
if ! runuser -u www-data -- php artisan config:cache --no-interaction \
    || ! systemctl reload "$fpm"; then
    mv -f "$anterior" "$env"
    runuser -u www-data -- php artisan config:cache --no-interaction
    systemctl reload "$fpm"
    echo 'No se pudo activar Gemini. Se restauro la configuracion anterior.' >&2
    exit 1
fi
echo 'Gemini activo para los numeros indicados de la demo.'
'@

$bytes = [Text.Encoding]::UTF8.GetBytes(($remoto -replace '\r', ''))
$codificado = [Convert]::ToBase64String($bytes)
try {
    $comando = 'bash -c ''bash <(printf %s {0} | base64 -d) /root/{1} {2} {3}''' -f $codificado, $nombre, $TelefonosDemo, [int]$ActualizarDemoActiva.IsPresent
    $clave | ssh.exe -T -o NumberOfPasswordPrompts=1 -p 5644 $servidor $comando
}
finally {
    $clave = $null
}
if ($LASTEXITCODE -ne 0) { throw 'La publicacion o activacion de Gemini fallo. Revisar la salida anterior.' }
