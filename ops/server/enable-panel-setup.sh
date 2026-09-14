#!/usr/bin/env bash
set -euo pipefail

if [[ $EUID != 0 ]]; then
    echo 'Run this setup helper as root.' >&2
    exit 1
fi

shared=/srv/kuhni/design/shared
marker="$shared/setup/enabled"
credentials=/srv/kuhni/setup-access.txt
password_file=/etc/nginx/kuhni-setup.htpasswd

[[ -d "$shared/accounts" ]] || { echo 'Initialize the design deployment first.' >&2; exit 1; }
if [[ -n $(find "$shared/accounts" -mindepth 1 -maxdepth 1 -type d -print -quit) ]]; then
    echo 'An account already exists. Use the Panel to manage users.' >&2
    exit 1
fi

if [[ -f "$marker" && -f "$credentials" && -f "$password_file" ]]; then
    echo "Browser setup already enabled. Access details: $credentials"
    exit 0
fi

umask 077
install -d -m 755 -o kuhni-design -g kuhni-design "$shared/setup"
password=$(openssl rand -hex 24)
password_hash=$(printf '%s\n' "$password" | openssl passwd -apr1 -stdin)
printf 'setup:%s\n' "$password_hash" > "$password_file"
chown root:www-data "$password_file"
chmod 640 "$password_file"
printf 'URL: https://design.studiya-kuhni-kmv.ru/panel\nUsername: setup\nPassword: %s\n\nThis temporary browser gate disappears after creating the first Kirby administrator.\n' "$password" > "$credentials"
chown root:root "$credentials"
chmod 600 "$credentials"
unset password password_hash

# Enable the installer only after the separate Nginx credentials are ready.
install -m 600 -o kuhni-design -g kuhni-design /dev/null "$marker"
echo "Browser setup enabled. Access details: $credentials"
