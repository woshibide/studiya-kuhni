#!/usr/bin/env bash
set -euo pipefail
[[ $EUID == 0 ]] || exit 1
mode=${1:-all}
[[ $mode == all || $mode == main ]] || { echo 'Usage: enable-https.sh [all|main]' >&2; exit 2; }
[[ -f /srv/kuhni/notice/current/index.html ]] || { echo 'Deploy notice first.' >&2; exit 1; }
if [[ $mode == all ]]; then
    [[ -f /srv/kuhni/design/current/index.php ]] || { echo 'Deploy design first.' >&2; exit 1; }
    [[ -f /srv/kuhni/design/shared/setup/enabled || -n $(find /srv/kuhni/design/shared/accounts -mindepth 2 -maxdepth 2 -name index.php -print -quit) ]] || { echo 'Enable protected browser setup or create the first administrator.' >&2; exit 1; }
fi
python3 - "$mode" <<'PY'
import socket, sys
hosts = ['studiya-kuhni-kmv.ru', 'www.studiya-kuhni-kmv.ru', 'xn-----flcfubncsg3bmne4a0n.xn--p1ai', 'www.xn-----flcfubncsg3bmne4a0n.xn--p1ai']
if sys.argv[1] == 'all': hosts.append('design.studiya-kuhni-kmv.ru')
for host in hosts:
    addresses = {item[4][0] for item in socket.getaddrinfo(host, 80, socket.AF_UNSPEC, socket.SOCK_STREAM)}
    if addresses != {'80.76.60.110'}:
        raise SystemExit(f'Fix DNS for {host}: {addresses}')
print('All selected DNS hosts point to this server.')
PY
certbot certonly --non-interactive --agree-tos --keep-until-expiring --webroot -w /var/lib/letsencrypt --cert-name studiya-kuhni-kmv.ru \
    -d studiya-kuhni-kmv.ru -d www.studiya-kuhni-kmv.ru \
    -d xn-----flcfubncsg3bmne4a0n.xn--p1ai -d www.xn-----flcfubncsg3bmne4a0n.xn--p1ai
if [[ $mode == all ]]; then
    certbot certonly --non-interactive --agree-tos --keep-until-expiring --webroot -w /var/lib/letsencrypt --cert-name design.studiya-kuhni-kmv.ru -d design.studiya-kuhni-kmv.ru
fi
old_config=$(mktemp)
cp /etc/nginx/sites-available/kuhni.conf "$old_config"
config=/usr/local/lib/kuhni/kuhni-https.conf
if [[ $mode == main ]] && ! grep -q 'root /srv/kuhni/design/current;' "$old_config"; then
    config=/usr/local/lib/kuhni/kuhni-main-https.conf
fi
install -m 644 "$config" /etc/nginx/sites-available/kuhni.conf
if ! nginx -t; then
    cp "$old_config" /etc/nginx/sites-available/kuhni.conf
    rm "$old_config"
    exit 1
fi
systemctl reload nginx
health() {
    curl --fail --silent --show-error --retry 5 --retry-all-errors --retry-delay 1 --max-time 60 https://studiya-kuhni-kmv.ru/ >/dev/null || return 1
    if [[ $mode == all ]]; then curl --fail --silent --show-error --retry 5 --retry-all-errors --retry-delay 1 --max-time 60 https://design.studiya-kuhni-kmv.ru/ >/dev/null || return 1; fi
    curl --fail --silent --show-error --retry 5 --retry-all-errors --retry-delay 1 --max-time 60 https://www.luxor-kmv.ru/ >/dev/null
}
if ! health; then
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
certbot renew --dry-run --no-random-sleep-on-renew --run-deploy-hooks --cert-name studiya-kuhni-kmv.ru
if [[ $mode == all ]]; then
    certbot renew --dry-run --no-random-sleep-on-renew --run-deploy-hooks --cert-name design.studiya-kuhni-kmv.ru
fi
echo "HTTPS activation complete: $mode."
