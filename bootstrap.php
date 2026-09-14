<?php

declare(strict_types=1);

require_once __DIR__ . '/kirby/bootstrap.php';
require_once __DIR__ . '/site/plugins/studio-seo/OgImage.php';

$properties = ['roots' => ['index' => __DIR__], 'routes' => Studio\Seo\OgImage::routes()];
$shared = getenv('STUDIO_SHARED_ROOT');
if ($shared !== false && $shared !== '') {
    if ($shared[0] !== '/' || !is_dir($shared)) {
        throw new RuntimeException('STUDIO_SHARED_ROOT must be an existing absolute directory.');
    }
    $shared = realpath($shared);
    foreach (['content', 'media', 'accounts', 'sessions', 'cache', 'logs', 'storage', 'licenses'] as $directory) {
        if (!is_dir($shared . '/' . $directory)) {
            throw new RuntimeException('Missing shared directory: ' . $directory);
        }
        $properties['roots'][$directory] = $shared . '/' . $directory;
    }
    $properties['roots']['license'] = $shared . '/license/.license';
    $setupMarker = $shared . '/setup/enabled';
    $properties['options']['panel']['install'] = is_file($setupMarker) &&
        Kirby\Cms\Users::load($shared . '/accounts')->count() === 0;
    // Top-level extensions append to config and plugin hooks without replacing them.
    $properties['hooks']['user.create:after'] = function (Kirby\Cms\User $user) use ($setupMarker): void {
        if ($user->isAdmin() && is_file($setupMarker) && !unlink($setupMarker)) {
            throw new RuntimeException('Administrator created, but the setup marker could not be removed.');
        }
    };
}

return new Kirby($properties);
