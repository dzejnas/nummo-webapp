#!/bin/sh
# Hosting platforms tell the app which port to use in $PORT (default 80)
export PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
exec apache2-foreground
