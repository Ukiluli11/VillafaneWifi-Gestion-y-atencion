#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

if [[ ${EUID} -ne 0 ]]; then
    echo 'Ejecutar como root para respaldar MariaDB y los archivos privados.' >&2
    exit 1
fi

base=/srv/villafane-wifi
destino=/var/backups/villafane-wifi
mkdir -p "$destino"
chmod 700 "$destino"
exec 9>"$destino/.lock"
flock -x 9

temporal=$(mktemp -d "$destino/.en-proceso.XXXXXXXX")
trap 'rm -rf -- "$temporal"' EXIT
fecha=$(date -u +%Y%m%dT%H%M%SZ)

mariadb-dump --single-transaction --quick --routines --events \
    --databases villafane_wifi | gzip > "$temporal/base.sql.gz"
tar -czf "$temporal/archivos.tar.gz" -C "$base/shared" \
    --exclude='storage/logs' --exclude='storage/framework/cache' \
    --exclude='storage/framework/views' --exclude='storage/framework/sessions' \
    .env storage
tar -czf "$destino/.respaldo-$fecha.tar.gz" -C "$temporal" base.sql.gz archivos.tar.gz
mv "$destino/.respaldo-$fecha.tar.gz" "$destino/respaldo-$fecha.tar.gz"
find "$destino" -maxdepth 1 -type f -name 'respaldo-????????T??????Z.tar.gz' -mtime +7 -delete
echo 'Respaldo privado de base de datos y archivos completado.'
