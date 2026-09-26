#!/bin/sh
set -eu

attempt=0
until php /var/www/html/setup/scripts/deploy.php; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        echo "Database bootstrap failed after 30 attempts." >&2
        exit 1
    fi
    sleep 2
done

exec apache2-foreground
