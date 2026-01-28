#!/bin/bash

cd /var/www/html

# Optimisations Laravel au premier boot
if [ ! -f storage/framework/bootstrap_complete ]; then
    echo "[entrypoint] First boot - running optimizations..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan event:cache || true
    touch storage/framework/bootstrap_complete
fi

echo "[entrypoint] Starting process: ${PROCESS:-unknown}"
exec "$@"
