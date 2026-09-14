#!/usr/bin/env bash
set -euo pipefail
[[ $EUID == 0 ]] || { echo 'Run through ssh luxor as root.' >&2; exit 1; }
action=${1:-}
target=${2:-}
[[ $target == design || $target == notice ]] || exit 2
base=/srv/kuhni/$target
install -d -m 755 "$base/releases"
exec 9>/srv/kuhni/deploy.lock
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }
previous=$(readlink -f "$base/current" || true)
host=studiya-kuhni-kmv.ru
[[ $target != design ]] || host=design.studiya-kuhni-kmv.ru
health() {
    local body panel_status
    body=$(curl --fail --silent --show-error --retry 2 --retry-delay 1 --max-time 120 -H "Host: $host" http://127.0.0.1:8087/) || return 1
    [[ $body == *'<html'* && $body == *'<h1'* ]] || return 1
    if [[ $target == design ]]; then
        panel_status=$(curl --silent --show-error --max-time 30 -o /dev/null -w '%{http_code}' -H "Host: $host" http://127.0.0.1:8087/panel/login) || return 1
        if [[ -f $base/shared/setup/enabled ]]; then
            [[ $panel_status == 401 ]] || return 1
        else
            [[ $panel_status == 200 || $panel_status == 302 ]] || return 1
        fi
    fi
    curl --fail --silent --show-error --max-time 30 https://www.luxor-kmv.ru/ >/dev/null
}
switch_to() {
    ln -s "$1" "$base/.current-$$"
    mv -Tf "$base/.current-$$" "$base/current"
}
if [[ $action == rollback ]]; then
    destination=$(readlink -f "$base/previous" || true)
    [[ -n $destination && -f $destination/.healthy && $destination == "$base/releases/"* ]] || { echo 'No previous release.' >&2; exit 1; }
elif [[ $action == deploy ]]; then
    release=${3:-}
    mode=${4:-update}
    [[ $release =~ ^[0-9]{8}T[0-9]{6}Z-[a-f0-9]{12}$ && ( $mode == init || $mode == update ) ]] || exit 2
    destination=$base/releases/$release
    [[ ! -e $destination ]] || { echo 'Release already exists.' >&2; exit 1; }
    archive=/srv/kuhni/incoming/$release-$target.tar.gz
    [[ -f $archive ]] || exit 1
    if [[ $target == design ]]; then
        if [[ $mode == init ]]; then
            [[ ! -e $base/shared/.initialized && -z $(ls -A "$base/shared/content") ]] || { echo 'Content already initialized; use normal deploy.' >&2; exit 1; }
        else
            [[ -f $base/shared/.initialized ]] || { echo 'Run design --init first.' >&2; exit 1; }
        fi
    fi
    mkdir "$destination"
    python3 - "$archive" "$destination" <<'PY'
import sys, tarfile
with tarfile.open(sys.argv[1]) as archive:
    archive.extractall(sys.argv[2], filter='data')
PY
    chown -R root:root "$destination"
    chmod -R u=rwX,go=rX "$destination"
    if [[ $target == design ]]; then
        (cd "$destination" && composer check-platform-reqs --no-dev)
        php -l "$destination/bootstrap.php"
        if [[ $mode == init ]]; then
            [[ -d $destination/seed-content ]] || { echo 'Missing initial content.' >&2; exit 1; }
            rsync -a --chown=kuhni-design:kuhni-design "$destination/seed-content/" "$base/shared/content/"
            chmod -R u=rwX,g=rX,o= "$base/shared/content"
            touch "$base/shared/.initialized"
            rm -rf "$destination/seed-content"
        fi
        ln -s "$base/shared/media" "$destination/media"
    fi
else
    exit 2
fi
switch_to "$destination"
if ! health; then
    if [[ -n $previous && -d $previous ]]; then switch_to "$previous"; else rm "$base/current"; fi
    echo 'Health check failed; restored previous code pointer. Content unchanged.' >&2
    exit 1
fi
if [[ -n $previous && $previous != "$destination" ]]; then ln -sfn "$previous" "$base/previous"; fi
if [[ $action == deploy ]]; then
    touch "$destination/.healthy"
    rm "$archive"
    # Keep current, rollback target, and newest remaining release.
    python3 - "$base" <<'PY'
from pathlib import Path
import shutil, sys
base = Path(sys.argv[1])
keep = {(base / 'current').resolve(), (base / 'previous').resolve()}
releases = sorted((p for p in (base / 'releases').iterdir() if (p / '.healthy').is_file()), reverse=True)
for release in releases:
    if len(keep) < 3:
        keep.add(release)
for release in releases:
    if release not in keep and release.is_dir() and not release.is_symlink():
        shutil.rmtree(release)
PY
fi
echo "$target serves $(basename "$destination"); editorial data preserved."
