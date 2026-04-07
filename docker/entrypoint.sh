#!/bin/sh
set -e

# Fix storage directory permissions at runtime.
# Necessary on Windows WSL where volume mounts override build-time chown.
STORAGE_DIRS="
    /var/www/html/storage/cache/view
    /var/www/html/storage/cache/data
    /var/www/html/storage/logs
    /var/www/html/storage/queue
    /var/www/html/storage/sessions
"

for dir in $STORAGE_DIRS; do
    mkdir -p "$dir"
    chown -R www-data:www-data "$dir"
    chmod -R 775 "$dir"
done

exec "$@"
