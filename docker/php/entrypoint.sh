#!/bin/sh
set -e

# Bind mounts do Windows/WSL2 nem sempre chegam com permissao de escrita para
# o usuario www-data (que roda os workers do php-fpm). Garante os diretorios
# que o Laravel precisa escrever, sem falhar caso o mount seja read-only.
for dir in \
    storage \
    storage/app \
    storage/app/public \
    storage/framework \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
do
    [ -d "/var/www/html/$dir" ] || mkdir -p "/var/www/html/$dir" 2>/dev/null || true
done

chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

exec "$@"
