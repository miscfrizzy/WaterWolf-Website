#!/bin/bash

# Set UID/GID
PUID="${PUID:-1000}"
PGID="${PGID:-1000}"

groupmod -o -g "$PGID" app
usermod -o -u "$PUID" app

# Write environment variables to script
declare -p | grep -Ev 'BASHOPTS|BASH_VERSINFO|EUID|PPID|SHELLOPTS|UID' > /container.env
chmod 744 /container.env

# Clear Temp
shopt -s dotglob
rm -rf /var/app/www_tmp/*

# Set up self-signed SSL
export ACME_DIR="/var/app/ssl"
export APP_DIR="/var/app/www"

if [ -f "$ACME_DIR/default.crt" ]; then
    rm -rf "$ACME_DIR/default.key" || true
    rm -rf "$ACME_DIR/default.crt" || true
fi

if [ -f "$APP_DIR/build/dev/ssl/default.crt" ]; then
    cp "$APP_DIR/build/dev/ssl/default.crt" "$ACME_DIR/ssl.crt"
    cp "$APP_DIR/build/dev/ssl/default.key" "$ACME_DIR/ssl.key"
fi

# Generate a self-signed certificate if one doesn't exist in the certs path.
if [ ! -f "$ACME_DIR/default.crt" ]; then
    echo "Generating self-signed certificate..."

    openssl req -new -nodes -x509 -subj "/C=US/ST=Texas/L=Austin/O=IT/CN=localhost" \
        -days 365 -extensions v3_ca \
        -keyout "$ACME_DIR/default.key" \
        -out "$ACME_DIR/default.crt"
fi

if [ ! -e "$ACME_DIR/ssl.crt" ]; then
    rm -rf "$ACME_DIR/ssl.key" || true
    rm -rf "$ACME_DIR/ssl.crt" || true

    ln -s "$ACME_DIR/default.key" "$ACME_DIR/ssl.key"
    ln -s "$ACME_DIR/default.crt" "$ACME_DIR/ssl.crt"
fi

chown -R app:app "$ACME_DIR" || true
chmod -R u=rwX,go=rX "$ACME_DIR" || true

# Composer install
cd /var/app/www

su-exec app composer install
su-exec app npm ci

app_cli init

app_cli seed

exec "$@"
