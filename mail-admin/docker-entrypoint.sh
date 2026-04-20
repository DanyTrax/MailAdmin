#!/bin/bash
set -e

# Garantiza que los archivos de docker-mailserver existan y sean escribibles
# por el usuario de Apache (www-data) dentro del contenedor.
ACCOUNTS_FILE="${ACCOUNTS_FILE:-/mailconfig/postfix-accounts.cf}"
ALIASES_FILE="${ALIASES_FILE:-/mailconfig/postfix-virtual.cf}"
QUOTA_FILE="${QUOTA_FILE:-/mailconfig/dovecot-quotas.cf}"

mkdir -p "$(dirname "$ACCOUNTS_FILE")" || true

for f in "$ACCOUNTS_FILE" "$ALIASES_FILE" "$QUOTA_FILE"; do
    if [ ! -f "$f" ]; then
        touch "$f" 2>/dev/null || true
    fi
    chmod 666 "$f" 2>/dev/null || true
done

# Permisos del directorio para poder crear .tmp al hacer reemplazos atómicos.
chmod 777 "$(dirname "$ACCOUNTS_FILE")" 2>/dev/null || true

exec "$@"
