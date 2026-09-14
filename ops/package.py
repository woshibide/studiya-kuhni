#!/usr/bin/env python3
"""Package an exported, dependency-installed revision, never the live website."""
import json
import shutil
import sys
import tarfile
from pathlib import Path

source, output = map(Path, sys.argv[1:3])
target, release, mode = sys.argv[3:6]
output.mkdir()
if target == 'notice':
    shutil.copytree(source / 'ops/notice', output, dirs_exist_ok=True)
    for original, name in [
        ('assets/fonts/SuisseIntl-Medium.woff2', 'suisse-medium.woff2'),
        ('assets/media-kit/SVG/studiya_kuhni-wordmark long lockup.svg', 'wordmark.svg'),
        ('assets/icons/favicons/favicon.svg', 'favicon.svg'),
    ]:
        shutil.copy2(source / original, output / name)
else:
    # An allowlist excludes local tooling, runtime state and development exports.
    for name in ['index.php', 'bootstrap.php', 'composer.json', 'composer.lock', 'kirby', 'vendor', 'assets', 'site']:
        src, dst = source / name, output / name
        if src.is_dir():
            shutil.copytree(src, dst, ignore=shutil.ignore_patterns('.git', '.DS_Store', '.env', '.env.*'))
        else:
            shutil.copy2(src, dst)
    for name in ['accounts', 'sessions', 'cache', 'logs', 'storage', 'licenses', 'commands']:
        shutil.rmtree(output / 'site' / name, ignore_errors=True)
    (output / 'site/config/.license').unlink(missing_ok=True)
    if (output / 'vendor/bnomei/kirby-mcp').exists():
        raise SystemExit('Development MCP package remains in production artifact.')
    if mode == 'init':
        shutil.copytree(source / 'content', output / 'seed-content', ignore=shutil.ignore_patterns('.DS_Store', '.git'))

(output / 'release.json').write_text(json.dumps({'release': release, 'target': target}) + '\n')
with tarfile.open(str(output) + '.tar.gz', 'w:gz') as archive:
    for item in sorted(output.iterdir()):
        archive.add(item, arcname=item.name)
