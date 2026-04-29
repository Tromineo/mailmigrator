#!/bin/sh
set -e

# Inicia php-fpm em background
php-fpm -D

# Aguarda php-fpm estar pronto na porta 9000
for i in $(seq 1 10); do
    if nc -z 127.0.0.1 9000 2>/dev/null; then
        break
    fi
    sleep 0.5
done

# Inicia nginx em foreground
exec nginx -g "daemon off;"
