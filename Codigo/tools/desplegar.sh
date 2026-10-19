#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

if [[ ${EUID} -ne 0 || $# -ne 2 ]]; then
    echo 'Uso: sudo bash desplegar.sh paquete.tar.gz php8.5-fpm' >&2
    exit 1
fi

paquete=$(realpath "$1")
servicio_php=$2
[[ -f "$paquete" && "$servicio_php" =~ ^php8\.[3-9]-fpm$ ]] || exit 1
base=/srv/villafane-wifi
install -d -o root -g www-data -m 0750 "$base" "$base/releases" "$base/shared"
exec 8>"$base/.deploy.lock"
flock -n 8 || { echo 'Ya hay otro despliegue en curso.' >&2; exit 1; }
[[ -f "$base/shared/.env" ]] || { echo 'Falta configurar shared/.env.' >&2; exit 1; }

listado=$(tar -tzf "$paquete")
if grep -Eq '(^/|(^|/)\.\.(/|$)|(^|/)(\.env|vendor|storage|node_modules|\.git)(/|$))' <<< "$listado"; then
    echo 'El paquete contiene rutas no permitidas.' >&2
    exit 1
fi

version=$(date -u +%Y%m%dT%H%M%SZ)-$$
release="$base/releases/$version"
mkdir "$release"
tar -xzf "$paquete" -C "$release" --no-same-owner --no-same-permissions
[[ -f "$release/artisan" && -f "$release/public/build/manifest.json" ]] || {
    echo 'Falta la aplicación o los recursos compilados.' >&2
    exit 1
}

for directorio in app/private app/public framework/cache/data framework/sessions framework/views logs; do
    install -d -o www-data -g www-data -m 2770 "$base/shared/storage/$directorio"
done
chown root:www-data "$base/shared/.env"
chmod 640 "$base/shared/.env"
ln -s "$base/shared/.env" "$release/.env"
ln -s "$base/shared/storage" "$release/storage"
install -d -o www-data -g www-data -m 2770 "$release/bootstrap/cache"
cd "$release"

COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
chown -R root:www-data "$release"
chmod -R g+rX,o-rwx "$release"
chown -R www-data:www-data "$release/bootstrap/cache"

dominio=$(runuser -u www-data -- php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$valid = app()->environment("production")
    && !config("app.debug")
    && str_starts_with((string) config("app.url"), "https://")
    && (string) config("app.key") !== ""
    && config("session.secure") === true
    && config("database.default") === "mariadb"
    && (string) config("database.connections.mariadb.host") === "127.0.0.1"
    && config("services.whatsapp.validar_firma") === true;
foreach (["verify_token", "access_token", "app_secret", "phone_number_id"] as $key) {
    $valid = $valid && (string) config("services.whatsapp.".$key) !== "";
}
if (!$valid) {
    fwrite(STDERR, "Revisar HTTPS, entorno de producción, sesiones y configuración de WhatsApp.\n");
    exit(1);
}
$app->make("db")->connection()->getPdo();
echo parse_url((string) config("app.url"), PHP_URL_HOST);
')
[[ "$dominio" =~ ^[a-zA-Z0-9.-]+$ ]] || { echo 'Dominio HTTPS inválido.' >&2; exit 1; }

bash "$release/tools/respaldar.sh"
runuser -u www-data -- php artisan migrate --force --no-interaction
php artisan storage:link --no-interaction
runuser -u www-data -- php artisan optimize --no-interaction

anterior=$(readlink "$base/current" || true)
ln -s "$release" "$base/.siguiente-$version"
mv -Tf "$base/.siguiente-$version" "$base/current"
systemctl reload "$servicio_php"

if ! estado=$(curl --fail --silent --show-error --max-time 20 --retry 2 \
    --resolve "$dominio:443:127.0.0.1" --output /dev/null --write-out '%{http_code}' \
    "https://$dominio/up") || [[ "$estado" != 200 ]]; then
    if [[ -n "$anterior" ]]; then
        ln -s "$anterior" "$base/.volver-$version"
        mv -Tf "$base/.volver-$version" "$base/current"
        systemctl reload "$servicio_php"
    fi
    echo 'Falló la comprobación HTTP. Revisar antes de continuar; no se revirtieron las migraciones.' >&2
    exit 1
fi

echo "Despliegue completado: $version. Base de datos y comprobantes conservados."
