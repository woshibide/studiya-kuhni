#!/usr/bin/env bash
set -euo pipefail
[[ $EUID == 0 ]] || exit 1
[[ -f /srv/kuhni/notice/current/index.html && -f /srv/kuhni/design/current/index.php ]] || { echo 'Deploy both sites first.' >&2; exit 1; }
[[ -n $(find /srv/kuhni/design/shared/accounts -mindepth 2 -maxdepth 2 -name index.php -print -quit) ]] || { echo 'Create the first administrator before opening design.' >&2; exit 1; }
python3 - <<'PY'
import socket
hosts = ['studiya-kuhni-kmv.ru', 'www.studiya-kuhni-kmv.ru', 'design.studiya-kuhni-kmv.ru', 'xn-----flcfubncsg3bmne4a0n.xn--p1ai', 'www.xn-----flcfubncsg3bmne4a0n.xn--p1ai']
for host in hosts:
    addresses = {item[4][0] for item in socket.getaddrinfo(host, 80, socket.AF_UNSPEC, socket.SOCK_STREAM)}
    if addresses != {'80.76.60.110'}:
        raise SystemExit(f'Fix DNS for {host}: {addresses}')
print('All five DNS hosts point to this server.')
PY
certbot certonly --non-interactive --agree-tos --webroot -w /var/lib/letsencrypt --cert-name studiya-kuhni-kmv.ru \
    -d studiya-kuhni-kmv.ru -d www.studiya-kuhni-kmv.ru \
    -d xn-----flcfubncsg3bmne4a0n.xn--p1ai -d www.xn-----flcfubncsg3bmne4a0n.xn--p1ai
certbot certonly --non-interactive --agree-tos --webroot -w /var/lib/letsencrypt --cert-name design.studiya-kuhni-kmv.ru -d design.studiya-kuhni-kmv.ru
old_config=$(mktemp)
cp /etc/nginx/sites-available/kuhni.conf "$old_config"
install -m 644 /usr/local/lib/kuhni/kuhni-https.conf /etc/nginx/sites-available/kuhni.conf
if ! nginx -t; then
    cp "$old_config" /etc/nginx/sites-available/kuhni.conf
    rm "$old_config"
    exit 1
fi
systemctl reload nginx
if ! curl --fail --silent --show-error https://studiya-kuhni-kmv.ru/ >/dev/null || \
   ! curl --fail --silent --show-error https://design.studiya-kuhni-kmv.ru/ >/dev/null || \
   ! curl --fail --silent --show-error https://www.luxor-kmv.ru/ >/dev/null; then
    cp "$old_config" /etc/nginx/sites-available/kuhni.conf
    nginx -t && systemctl reload nginx
    rm "$old_config"
    exit 1
fi
rm "$old_config"
cat > /etc/letsencrypt/renewal-hooks/deploy/kuhni-nginx <<'HOOK'
#!/usr/bin/env bash
set -euo pipefail
case "${RENEWED_LINEAGE:-}" in
    /etc/letsencrypt/live/studiya-kuhni-kmv.ru|/etc/letsencrypt/live/design.studiya-kuhni-kmv.ru)
        nginx -t && systemctl reload nginx ;;
esac
HOOK
chmod 755 /etc/letsencrypt/renewal-hooks/deploy/kuhni-nginx
certbot renew --dry-run --cert-name studiya-kuhni-kmv.ru
certbot renew --dry-run --cert-name design.studiya-kuhni-kmv.ru
echo 'Notice and design published with HTTPS.'
