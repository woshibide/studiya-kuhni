<?php

// Used only by seo-panel.mjs with an isolated, randomly named temporary directory.
$temporary = getenv('STUDIO_SEO_TEST_ROOT');
if (!$temporary || !is_dir($temporary) || !str_starts_with(realpath($temporary), realpath(sys_get_temp_dir()) . '/studio-seo-panel-')) {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
require $root . '/kirby/bootstrap.php';
require_once $root . '/site/plugins/studio-seo/OgImage.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$kirby = new Kirby\Cms\App([
    'roots' => [
        'index' => $root, 'content' => $temporary . '/content', 'accounts' => $temporary . '/accounts',
        'sessions' => $temporary . '/sessions', 'cache' => $temporary . '/cache', 'media' => $temporary . '/media',
    ],
    'routes' => Studio\Seo\OgImage::routes(),
    'options' => ['debug' => true, 'studio.productionUrl' => 'https://studio.example.com', 'auth.challenge.email' => false],
]);
if (PHP_SAPI === 'cli') {
    Kirby\Filesystem\Dir::copy($root . '/content', $temporary . '/content');
    $kirby->impersonate('kirby');
    $kirby->users()->create(['email' => 'seo-editor@example.test', 'password' => 'local-seo-fixture-password', 'role' => 'admin', 'language' => 'ru']);
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
