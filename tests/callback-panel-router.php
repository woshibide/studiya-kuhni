<?php

// Isolated browser fixture only. No real accounts, callback data, or SMTP transport.
$temporary = getenv('STUDIO_CALLBACK_TEST_ROOT');
if (!$temporary || !is_dir($temporary) || !str_starts_with(realpath($temporary), realpath(sys_get_temp_dir()) . '/studio-callback-panel-')) {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
require $root . '/kirby/bootstrap.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
// Simulate the HTTPS production request boundary while the local test uses HTTP.
$_SERVER['HTTPS'] = 'on';
$port = getenv('STUDIO_CALLBACK_TEST_PORT') ?: '8019';
$kirby = new Kirby\Cms\App([
    'roots' => [
        'index' => $root, 'content' => $temporary . '/content', 'accounts' => $temporary . '/accounts',
        'sessions' => $temporary . '/sessions', 'cache' => $temporary . '/cache', 'media' => $temporary . '/media',
    ],
    'urls' => ['index' => 'http://127.0.0.1:' . $port],
    'options' => [
        'debug' => true, 'studio.environment' => 'production', 'studio.productionUrl' => 'https://127.0.0.1:' . $port,
        'auth.challenge.email' => false, 'studio.callback.storage' => is_file($temporary . '/fail-storage') ? $temporary . '/missing' : $temporary,
        'studio.callback.enabled' => true, 'studio.callback.from' => 'sender@example.test',
        'studio.callback.to' => 'inbox@example.test', 'studio.callback.transport' => ['type' => 'smtp', 'host' => 'unused.invalid'],
    ],
    'blueprints' => ['users/callback-editor' => ['name' => 'callback-editor', 'title' => 'Editor', 'permissions' => ['access' => ['panel' => true, 'site' => true], 'users' => false, 'user' => ['changeRole' => false]]]],
    'components' => ['email' => static function ($kirby, array $props) use ($temporary) {
        if (is_file($temporary . '/fail-email')) throw new RuntimeException('Fake SMTP failure');
        return new Kirby\Email\Email($props);
    }],
]);
if (PHP_SAPI === 'cli') {
    Kirby\Filesystem\Dir::copy($root . '/content', $temporary . '/content');
    $kirby->impersonate('kirby');
    foreach (['admin', 'manager', 'callback-manager', 'callback-editor'] as $role) {
        $kirby->users()->create(['email' => $role . '@example.test', 'password' => 'local-callback-fixture-password', 'role' => $role, 'language' => 'ru']);
    }
    exit;
}
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$mediaPath = realpath($temporary . $uri);
if (str_starts_with($uri, '/media/') && $mediaPath && str_starts_with($mediaPath, $temporary . '/media/') && is_file($mediaPath)) {
    header('Content-Type: ' . match (pathinfo($uri, PATHINFO_EXTENSION)) { 'js' => 'text/javascript', 'css' => 'text/css', 'svg' => 'image/svg+xml', default => mime_content_type($mediaPath) });
    readfile($mediaPath);
    exit;
}
if (preg_match('~^/(assets|kirby/panel)/~', $uri) && is_file($root . $uri)) return false;
echo $kirby->render();
