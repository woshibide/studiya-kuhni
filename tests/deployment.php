<?php

// Exercise the real bootstrap against two code releases and one persistent root.
declare(strict_types=1);
$root = dirname(__DIR__);
require $root . '/kirby/bootstrap.php';
$temporary = realpath(sys_get_temp_dir()) . '/studio-deployment-' . bin2hex(random_bytes(5));
mkdir($temporary);
$shared = $temporary . '/shared';
mkdir($shared);
foreach (['content', 'media', 'accounts', 'sessions', 'cache', 'logs', 'storage', 'licenses', 'license'] as $directory) mkdir($shared . '/' . $directory);
file_put_contents($shared . '/content/site.txt', "Title: Persistent studio\n");
file_put_contents($shared . '/logs/feedback-fixture', 'keep feedback');
file_put_contents($shared . '/media/upload-fixture', 'keep uploads');
$probe = <<<'PHP'
$kirby = require $argv[1] . '/bootstrap.php';
echo json_encode(['title' => $kirby->site()->title()->value(), 'content' => $kirby->root('content'), 'logs' => $kirby->root('logs'), 'media' => $kirby->root('media'), 'install' => $kirby->option('panel.install'), 'index' => $kirby->root('index')]);
PHP;
try {
    foreach (['first', 'second', 'first'] as $name) {
        $release = $temporary . '/' . $name;
        if (!is_dir($release)) {
            mkdir($release);
            copy($root . '/bootstrap.php', $release . '/bootstrap.php');
            foreach (['kirby', 'vendor', 'site', 'assets'] as $directory) symlink($root . '/' . $directory, $release . '/' . $directory);
        }
        $pipes = [];
        $process = proc_open([PHP_BINARY, '-r', $probe, $release], [1 => ['pipe', 'w'], 2 => STDERR], $pipes, $root, array_merge(getenv(), ['STUDIO_SHARED_ROOT' => $shared, 'STUDIO_ENV' => 'staging']));
        $result = json_decode(stream_get_contents($pipes[1]), true);
        fclose($pipes[1]);
        if (proc_close($process) !== 0 || !$result || $result['title'] !== 'Persistent studio' || $result['content'] !== $shared . '/content' || $result['logs'] !== $shared . '/logs' || $result['media'] !== $shared . '/media' || $result['install'] !== false || $result['index'] !== $release) throw new RuntimeException('Shared bootstrap failed for ' . $name);
        if (file_get_contents($shared . '/logs/feedback-fixture') !== 'keep feedback' || file_get_contents($shared . '/media/upload-fixture') !== 'keep uploads') throw new RuntimeException('Persistent state changed.');
    }
    echo "Deployment bootstrap: shared roots, release switch, rollback and closed installer passed.\n";
} finally {
    foreach (['first', 'second'] as $name) {
        foreach (['kirby', 'vendor', 'site', 'assets'] as $directory) @unlink($temporary . '/' . $name . '/' . $directory);
    }
    Kirby\Filesystem\Dir::remove($temporary);
}
