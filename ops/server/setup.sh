#!/usr/bin/env bash
set -euo pipefail
[[ $EUID == 0 ]] || exit 1
cd "$(dirname "$0")/.."
php_version=$(dpkg-query -W -f='${Version}' php8.5-common)
if ! php -r 'exit(extension_loaded("sqlite3") && extension_loaded("pdo_sqlite") ? 0 : 1);'; then
    apt-get install -y "php8.5-sqlite3=$php_version"
fi
id kuhni-design >/dev/null 2>&1 || useradd --system --user-group --home-dir /srv/kuhni/design --shell /usr/sbin/nologin kuhni-design
install -d -m 755 /srv/kuhni /srv/kuhni/design /srv/kuhni/notice /srv/kuhni/design/releases /srv/kuhni/notice/releases
install -d -m 700 /srv/kuhni/incoming
install -d -m 751 -o root -g kuhni-design /srv/kuhni/design/shared
for directory in content accounts sessions cache logs storage licenses license tmp; do
    install -d -m 750 -o kuhni-design -g kuhni-design "/srv/kuhni/design/shared/$directory"
done
install -d -m 755 -o kuhni-design -g kuhni-design /srv/kuhni/design/shared/media
install -d -m 755 /usr/local/lib/kuhni /var/lib/letsencrypt/.well-known/acme-challenge
install -m 755 server/release.sh server/create-admin.php server/enable-https.sh server/enable-panel-setup.sh /usr/local/lib/kuhni/
install -m 644 php/kuhni-design.conf /etc/php/8.5/fpm/pool.d/kuhni-design.conf
install -m 644 nginx/design-app.conf /etc/nginx/snippets/kuhni-design-app.conf
install -m 644 nginx/notice-app.conf /etc/nginx/snippets/kuhni-notice-app.conf
install -m 644 nginx/kuhni-setup.conf /etc/nginx/conf.d/kuhni-setup.conf
install -m 644 nginx/kuhni-local.conf /etc/nginx/sites-available/kuhni-local.conf
install -m 644 nginx/kuhni-https.conf /usr/local/lib/kuhni/kuhni-https.conf
install -m 644 nginx/kuhni-main-https.conf /usr/local/lib/kuhni/kuhni-main-https.conf
if [[ ! -f /etc/nginx/sites-available/kuhni.conf ]]; then
    install -m 644 nginx/kuhni-http.conf /etc/nginx/sites-available/kuhni.conf
fi
ln -sfn /etc/nginx/sites-available/kuhni-local.conf /etc/nginx/sites-enabled/kuhni-local.conf
ln -sfn /etc/nginx/sites-available/kuhni.conf /etc/nginx/sites-enabled/kuhni.conf
php-fpm8.5 -t
nginx -t
systemctl reload php8.5-fpm
systemctl reload nginx
curl --fail --silent --show-error --max-time 30 https://www.luxor-kmv.ru/ >/dev/null
echo 'Kuhni pool and ACME routes ready; Luxor responds normally.'
