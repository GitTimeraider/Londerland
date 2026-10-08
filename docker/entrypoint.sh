#!/bin/sh
# Londerland container start-up.
#
# Works in two ways:
#  - started as root (default): the data folder is given to www-data, Apache drops to www-data
#  - started with --user UID:GID (for example 99:100): everything runs as that user and nothing needs root
#    or any capability, so --security-opt=no-new-privileges:true and --cap-drop=ALL work as they are
# As root with --cap-drop=ALL, add back CHOWN, DAC_READ_SEARCH, SETUID and SETGID (see the README).
set -e

APP_DIR=/var/www/html
DATA_DIR="$APP_DIR/data"
export TZ="${TZ:-UTC}"

if [ "$(id -u)" = "0" ]; then
    mkdir -p "$DATA_DIR"
    # A freshly mounted volume may be owned by root; the web server runs as www-data.
    # Look at the whole tree, so a change that was cut off earlier is finished; a folder find cannot read counts too.
    if [ -n "$(find "$DATA_DIR" \( ! -user www-data -o ! -group www-data \) -print -quit 2>/dev/null || echo unreadable)" ]; then
        if ! chown -R www-data:www-data "$DATA_DIR"; then
            echo "Londerland: could not give $DATA_DIR to www-data (uid $(id -u www-data))." >&2
            echo "With --cap-drop=ALL, also add: --cap-add=CHOWN --cap-add=DAC_READ_SEARCH --cap-add=SETUID --cap-add=SETGID" >&2
            echo "Or run with --user UID:GID, which needs no capabilities at all." >&2
            exit 1
        fi
    fi
    RUN_JOBS_AS="setpriv --reuid=www-data --regid=www-data --init-groups"
else
    if ! mkdir -p "$DATA_DIR" 2>/dev/null || [ ! -w "$DATA_DIR" ]; then
        echo "Londerland: $DATA_DIR is not writable for user $(id -u):$(id -g)." >&2
        echo "Give the host folder you mounted there to that user, for example:" >&2
        echo "    chown -R $(id -u):$(id -g) /path/to/londerland-data" >&2
        exit 1
    fi
    RUN_JOBS_AS=""
fi

# Scheduled jobs (backups, plugin tasks, ...): run the job runner at the start of every minute.
# This replaces the cron daemon, which needs root.
(
    while true; do
        sleep $((60 - $(date +%s) % 60))
        $RUN_JOBS_AS php "$APP_DIR/cron.php" > /dev/null 2>&1 || true
    done
) &

exec "$@"
