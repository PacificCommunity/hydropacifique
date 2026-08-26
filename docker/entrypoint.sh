#!/bin/sh
# Ensure the writable data tree exists before Apache starts.
#
# docker-compose bind-mounts ./data over /var/www/html/data, which masks the
# directories and ownership the image set up at build time. Everything under
# include/structure/export/ writes into data/export/, so a host directory that
# is missing (fresh clone: .gitignore keeps /data/* out of git) or owned by the
# host user makes every export fail with a 500.
set -e

for dir in export export/temp uploads/files corrections photos_station csv pdf html txt
do
    mkdir -p "/var/www/html/data/$dir"
done

# Bind-mounted dirs arrive owned by the host uid; www-data must own them to write.
chown -R www-data:www-data /var/www/html/data 2>/dev/null || \
    echo "entrypoint: warning - could not chown /var/www/html/data; exports may fail" >&2

exec docker-php-entrypoint "$@"
