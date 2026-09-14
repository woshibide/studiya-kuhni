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
echo json_encode(['title' => $kirby->site()->title()->value(), 'content' => $kirby->root('content'), 'logs' => $kirby->root('logs'), 'media' => $kirby->root('media'), 'install' => $kirby->option('panel.install', false), 'index' => $kirby->root('index'), 'installed' => $kirby->system()->isInstalled()]);
PHP;
$run = static function (string $code, string $release, ?string $sharedRoot = null) use ($root): array {
    $environment = array_merge(getenv(), ['STUDIO_SHARED_ROOT' => $sharedRoot ?? '', 'STUDIO_ENV' => 'staging']);
    $process = proc_open([PHP_BINARY, '-r', $code, $release], [1 => ['pipe', 'w'], 2 => STDERR], $pipes, $root, $environment);
    if (!is_resource($process)) throw new RuntimeException('Could not start the bootstrap probe.');
    $result = json_decode(stream_get_contents($pipes[1]), true);
    fclose($pipes[1]);
    if (proc_close($process) !== 0 || !is_array($result)) throw new RuntimeException('Bootstrap probe failed.');
    return $result;
};
try {
    foreach (['first', 'second', 'first'] as $name) {
        $release = $temporary . '/' . $name;
        if (!is_dir($release)) {
            mkdir($release);
            copy($root . '/bootstrap.php', $release . '/bootstrap.php');
            foreach (['kirby', 'vendor', 'assets'] as $directory) symlink($root . '/' . $directory, $release . '/' . $directory);
            mkdir($release . '/site');
            foreach (['plugins', 'blueprints'] as $directory) symlink($root . '/site/' . $directory, $release . '/site/' . $directory);
            mkdir($release . '/site/config');
            // Exercise an existing config hook alongside the bootstrap extension.
            file_put_contents($release . '/site/config/config.php', '<?php return array_replace(require ' . var_export($root . '/site/config/config.php', true) . ', ["hooks" => ["user.create:after" => function ($user): void { file_put_contents($user->kirby()->root("logs") . "/config-hook", $user->id()); }]]);');
        }
        $result = $run($probe, $release, $shared);
        if ($result['title'] !== 'Persistent studio' || $result['content'] !== $shared . '/content' || $result['logs'] !== $shared . '/logs' || $result['media'] !== $shared . '/media' || $result['install'] !== false || $result['index'] !== $release) throw new RuntimeException('Shared bootstrap failed for ' . $name);
        if (file_get_contents($shared . '/logs/feedback-fixture') !== 'keep feedback' || file_get_contents($shared . '/media/upload-fixture') !== 'keep uploads') throw new RuntimeException('Persistent state changed.');
    }
    mkdir($shared . '/setup');
    file_put_contents($shared . '/setup/enabled', '');
    $result = $run($probe, $release, $shared);
    if ($result['install'] !== true || $result['installed'] !== false) throw new RuntimeException('The setup marker did not enable the fresh installer.');

    $createAdministrator = <<<'PHP'
$kirby = require $argv[1] . '/bootstrap.php';
$kirby->impersonate('kirby');
$user = $kirby->users()->create(['email' => 'deployment-test@example.com', 'password' => bin2hex(random_bytes(20)), 'role' => 'admin']);
echo json_encode(['id' => $user->id(), 'admin' => $user->isAdmin(), 'marker' => is_file(getenv('STUDIO_SHARED_ROOT') . '/setup/enabled'), 'configHook' => file_get_contents($kirby->root('logs') . '/config-hook')]);
PHP;
    $result = $run($createAdministrator, $release, $shared);
    if (!$result['admin'] || $result['marker'] || $result['configHook'] !== $result['id']) throw new RuntimeException('Creating the first administrator did not close setup or preserve the config hook.');
    $result = $run($probe, $release, $shared);
    if ($result['install'] !== false || $result['installed'] !== true) throw new RuntimeException('Setup remained enabled after the first administrator.');

    file_put_contents($shared . '/setup/enabled', '');
    $result = $run($probe, $release, $shared);
    if ($result['install'] !== false) throw new RuntimeException('An existing account did not block setup with a stale marker.');
    unlink($shared . '/setup/enabled');

    $localProbe = <<<'PHP'
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$kirby = require $argv[1] . '/bootstrap.php';
echo json_encode(['content' => $kirby->root('content'), 'accounts' => $kirby->root('accounts'), 'installable' => $kirby->system()->isInstallable()]);
PHP;
    $result = $run($localProbe, $release);
    if ($result['content'] !== $release . '/content' || $result['accounts'] !== $release . '/site/accounts' || $result['installable'] !== true) throw new RuntimeException('Local roots or the local installer changed.');
    echo "Deployment bootstrap: persistent roots, release rollback, gated setup, first-admin cleanup, preserved hooks and local behavior passed.\n";
} finally {
    foreach (['first', 'second'] as $name) {
        foreach (['kirby', 'vendor', 'assets'] as $directory) @unlink($temporary . '/' . $name . '/' . $directory);
        foreach (['plugins', 'blueprints'] as $directory) @unlink($temporary . '/' . $name . '/site/' . $directory);
    }
    Kirby\Filesystem\Dir::remove($temporary);
}
