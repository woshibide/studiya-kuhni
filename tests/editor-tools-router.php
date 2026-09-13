<?php

// This router only runs against an isolated temporary test installation.
$temporary = getenv('STUDIO_EDITOR_TOOLS_TEST_ROOT');
if (!$temporary || !is_dir($temporary) || !str_starts_with(realpath($temporary), realpath(sys_get_temp_dir()) . '/studio-editor-tools-')) {
    http_response_code(404);
    exit;
}
$temporary = realpath($temporary);
$root = dirname(__DIR__);
require $root . '/kirby/bootstrap.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$kirby = new Kirby\Cms\App([
    'roots' => [
        'index' => $root, 'content' => $temporary . '/content', 'accounts' => $temporary . '/accounts',
        'sessions' => $temporary . '/sessions', 'cache' => $temporary . '/cache',
        'media' => $temporary . '/media', 'logs' => $temporary . '/logs',
    ],
    'options' => ['auth.challenge.email' => false],
]);
if (PHP_SAPI === 'cli') {
    Kirby\Filesystem\Dir::copy($root . '/content', $temporary . '/content');
    $kirby->impersonate('kirby');
    $kirby->users()->create(['email' => 'tools@example.test', 'password' => 'local-editor-tools-password', 'role' => 'admin', 'language' => 'ru']);
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
