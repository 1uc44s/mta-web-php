#!/usr/bin/env bash
# Meridyen Roleplay — günlük yedek (docs/OPERATIONS.md §4).
# Gerekli: mariadb-dump, age, (isteğe bağlı) rclone. Parolalar dosyada değil, ~/.my.cnf (meridyen_backup) içindedir.
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/meridyen}"
DB_NAME="${DB_NAME:-meridyen}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/meridyen}"
AGE_RECIPIENT_FILE="${AGE_RECIPIENT_FILE:-/etc/meridyen/backup.age.pub}"
REMOTE="${REMOTE:-}"            # ör. s3remote:meridyen-backups (rclone)
KEEP_DAILY_DAYS="${KEEP_DAILY_DAYS:-30}"

stamp="$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$BACKUP_DIR/daily" "$BACKUP_DIR/monthly"
umask 077

db_file="$BACKUP_DIR/daily/db-$stamp.sql.gz.age"
mariadb-dump --single-transaction --routines --triggers --hex-blob --default-character-set=utf8mb4 "$DB_NAME" \
  | gzip -9 | age -R "$AGE_RECIPIENT_FILE" > "$db_file"

files_file="$BACKUP_DIR/daily/files-$stamp.tar.gz.age"
tar -C "$APP_DIR/storage/app" -czf - private public | age -R "$AGE_RECIPIENT_FILE" > "$files_file"

# Ayın ilk günü aylık kopya (12 ay saklanır)
if [ "$(date -u +%d)" = "01" ]; then
  cp "$db_file" "$BACKUP_DIR/monthly/"
  find "$BACKUP_DIR/monthly" -type f -mtime +370 -delete
fi

find "$BACKUP_DIR/daily" -type f -mtime +"$KEEP_DAILY_DAYS" -delete

if [ -n "$REMOTE" ]; then
  rclone copy "$BACKUP_DIR" "$REMOTE" --immutable
fi

echo "[$stamp] yedek tamam: $(du -h "$db_file" | cut -f1) db, $(du -h "$files_file" | cut -f1) dosya"
