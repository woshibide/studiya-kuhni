#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
usage() { echo 'Usage: ops/deploy.sh design [--init] | notice | rollback design|notice' >&2; exit 2; }
[[ $# -ge 1 ]] || usage
if [[ $1 == rollback ]]; then
    [[ $# == 2 && ( $2 == design || $2 == notice ) ]] || usage
    ssh luxor /usr/local/lib/kuhni/release.sh rollback "$2"
    exit
fi
target=$1
mode=update
[[ $target == design || $target == notice ]] || usage
if [[ $# == 2 && $target == design && $2 == --init ]]; then mode=init
elif [[ $# != 1 ]]; then usage; fi
[[ -z $(git status --porcelain --untracked-files=normal) ]] || { echo 'Commit changes before deploying.' >&2; exit 1; }
revision=$(git rev-parse HEAD)
release="$(date -u +%Y%m%dT%H%M%SZ)-${revision:0:12}"
temporary=$(mktemp -d "${TMPDIR:-/tmp}/kuhni-deploy.XXXXXX")
temporary=$(cd "$temporary" && pwd -P)
trap 'rm -rf "$temporary"' EXIT
mkdir "$temporary/source"
git archive "$revision" | tar -xf - -C "$temporary/source"
if [[ $target == design ]]; then
    (cd "$temporary/source" && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction && composer check-platform-reqs --no-dev)
    (cd "$temporary/source/assets/js" && npm ci --ignore-scripts)
fi
python3 ops/package.py "$temporary/source" "$temporary/artifact" "$target" "$release" "$mode"
ssh luxor 'install -d -m 700 /srv/kuhni/incoming'
scp "$temporary/artifact.tar.gz" "luxor:/srv/kuhni/incoming/$release-$target.tar.gz"
ssh luxor /usr/local/lib/kuhni/release.sh deploy "$target" "$release" "$mode"
