#!/bin/sh
set -e

DATA_DIR=/var/www/html/data

# A freshly mounted volume may be owned by root; Organizr runs as www-data
mkdir -p "$DATA_DIR"
if [ "$(stat -c %U "$DATA_DIR")" != "www-data" ]; then
    chown -R www-data:www-data "$DATA_DIR"
fi

# Pass the timezone through to PHP (php.ini reads ${TZ})
export TZ="${TZ:-UTC}"
printf 'TZ=%s\n' "$TZ" > /etc/environment

# Run Organizr's cron jobs in the background
cron

exec "$@"
