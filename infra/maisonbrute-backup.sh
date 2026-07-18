#!/bin/sh
# Sauvegarde quotidienne de la base (commandes, comptes, catalogue, avis, journal).
# Dump compressé + rotation 14 jours. Installer :
#   sudo cp infra/maisonbrute-backup.sh /usr/local/bin/maisonbrute-backup
#   sudo chmod +x /usr/local/bin/maisonbrute-backup
#   sudo cp infra/maisonbrute-backup.cron /etc/cron.d/maisonbrute-backup
# Prérequis (droits pour l'utilisateur `deploy`) :
#   sudo mkdir -p /var/backups/maisonbrute && sudo chown deploy:deploy /var/backups/maisonbrute
#   sudo touch /var/log/maisonbrute-backup.log && sudo chown deploy:deploy /var/log/maisonbrute-backup.log
set -eu

PROJECT_DIR=/var/www/maisonbrute-app
BACKUP_DIR=/var/backups/maisonbrute
RETENTION_DAYS=14
COMPOSE="docker compose -f compose.prod.yaml"
STAMP=$(date +%F-%H%M%S)
OUT="$BACKUP_DIR/maisonbrute-$STAMP.sql.gz"

mkdir -p "$BACKUP_DIR"
cd "$PROJECT_DIR"

# pg_dump via le conteneur database ; `exec` ne recrée rien.
if $COMPOSE exec -T database pg_dump -U "${POSTGRES_USER:-app}" "${POSTGRES_DB:-app}" | gzip > "$OUT"; then
    echo "$(date -Is) OK  $OUT ($(du -h "$OUT" | cut -f1))"
else
    echo "$(date -Is) ECHEC de la sauvegarde" >&2
    rm -f "$OUT"
    exit 1
fi

# Rotation.
find "$BACKUP_DIR" -name 'maisonbrute-*.sql.gz' -mtime +"$RETENTION_DAYS" -delete
