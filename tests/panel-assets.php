<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';

use Kirby\Cms\App;
use Kirby\Filesystem\Dir;
use Kirby\Panel\Assets;
use Kirby\Panel\Plugins;

$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/studio-panel-assets-' . bin2hex(random_bytes(8));
mkdir($temporary . '/config', 0700, true);
try {
    $kirby = new App([
        'roots' => [
            'index' => $root, 'config' => $temporary . '/config', 'content' => $temporary . '/content',
            'media' => $temporary . '/media', 'cache' => $temporary . '/cache', 'sessions' => $temporary . '/sessions',
        ],
        'urls' => ['index' => 'https://studio.example.com'],
        'options' => ['debug' => true, 'studio.environment' => 'local'],
    ]);
    $plugins = new Plugins();
    $assets = (new Assets())->external();
    $bundle = $temporary . '/plugins.js';
    $manifest = $temporary . '/manifest.json';
    file_put_contents($bundle, $plugins->read('js'));
    file_put_contents($manifest, json_encode([
        'scripts' => $assets['js'],
        'css' => $assets['css'],
        'modified' => $plugins->modified(),
        'guide' => Studio\Panel::guide($kirby),
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    $command = ['node', $root . '/tests/panel-assets.cjs', $bundle, $manifest, $root];
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $root);
    if (!is_resource($process) || proc_close($process) !== 0) throw new RuntimeException('Panel client asset checks failed');
} finally {
    if (isset($kirby)) $kirby->session()->destroy();
    Dir::remove($temporary);
}
