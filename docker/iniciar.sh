#!/bin/sh
# Arranque del contenedor: Apache escucha en el puerto que indica Render (variable PORT)
set -e
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
export PORT
exec apache2-foreground
