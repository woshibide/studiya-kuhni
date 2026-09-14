<?php

$root = dirname(__DIR__);
$commands = [
    [PHP_BINARY, 'tests/deployment.php'],
    [PHP_BINARY, 'tests/site.php', 'local', 'localhost'],
    [PHP_BINARY, 'tests/site.php', 'staging', 'design.studio.example.com'],
    [PHP_BINARY, 'tests/site.php', 'production', 'studio.example.com'],
    [PHP_BINARY, 'tests/site.php', 'production', 'design.studio.example.com'],
    [PHP_BINARY, 'tests/blueprints.php'],
    [PHP_BINARY, 'tests/panel.php'],
    [PHP_BINARY, 'tests/panel-assets.php'],
    [PHP_BINARY, 'tests/seo.php'],
    [PHP_BINARY, 'tests/rich-text.php'],
    [PHP_BINARY, 'tests/section-library.php'],
    [PHP_BINARY, 'tests/mediakit-downloads.php'],
    [PHP_BINARY, 'tests/maps.php'],
    [PHP_BINARY, 'tests/fabric-map.php'],
    [PHP_BINARY, 'tests/callback.php'],
    [PHP_BINARY, 'tests/callback-route.php'],
    [PHP_BINARY, 'tests/callback-inbox.php'],
    ['node', 'tests/frontend.cjs'],
    ['node', 'tests/gallery.cjs'],
    ['node', 'tests/masonry.cjs'],
    ['node', 'tests/fabric-map.cjs'],
];
foreach ($commands as $command) {
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $root);
    if (!is_resource($process) || proc_close($process) !== 0) exit(1);
}
echo "All technical checks passed.\n";
