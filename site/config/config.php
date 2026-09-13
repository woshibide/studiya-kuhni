<?php

require_once dirname(__DIR__) . '/plugins/studio/Environment.php';
require_once dirname(__DIR__) . '/plugins/studio-panel/Panel.php';
require_once dirname(__DIR__) . '/plugins/studio-seo/OgImage.php';

$environment = Studio\Environment::name(getenv('STUDIO_ENV') ?: null);
$host = strtolower((string)parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST));
$localRequest = (in_array($host, ['localhost', '127.0.0.1', '[::1]'], true) &&
    in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) || PHP_SAPI === 'cli';

return [
    'ready' => static function ($kirby): array {
        $editorTools = PHP_SAPI !== 'cli' && $kirby->user() !== null &&
            !$kirby->request()->query()->get('_preview') &&
            $kirby->path() !== $kirby->option('api.slug', 'api') . '/' . $kirby->option('jr.static_site_generator.endpoint');

        return [
            'pechente.kirby-admin-bar.active' => $editorTools,
            'moinframe.loop.enabled' => $editorTools,
        ];
    },
    'moinframe.loop.public' => false,
    'moinframe.loop.position' => 'bottom',
    'moinframe.loop.welcome.enabled' => false,
    'mauricerenck.ogimage' => Studio\Seo\OgImage::options(),
    'cache.studio.seo.og' => ['active' => true],
    'debug' => $environment === 'local' && $localRequest,
    'studio.environment' => $environment,
    'studio.productionUrl' => Studio\Environment::origin(getenv('STUDIO_PRODUCTION_URL') ?: null),
    'studio.map.tileUrl' => 'https://tiles.maps.eox.at/wmts/1.0.0/s2cloudless_3857/default/g/{z}/{y}/{x}.jpg',
    'studio.map.attribution' => '<a href="https://cloudless.eox.at/">EOxCloudless</a> by <a href="https://eox.at/">EOX IT Services GmbH</a> (Contains modified Copernicus Sentinel data 2016) · <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a> · <a href="https://maps.eox.at/">EOX::Maps</a>',
    'studio.map.maxZoom' => 14,
    // Archive is intentionally excluded from the current launch in the templates.
    'studio.unpublishedPaths' => ['archive'],
    'session' => [
        'cookieName' => 'studio_' . $environment . '_session',
        'cookieDomain' => null,
    ],
    'panel' => [
        'menu' => static fn () => Studio\Panel::menu(kirby()),
        'viewButtons' => [
            'page' => Studio\Panel::pageButtons(),
            'site' => Studio\Panel::siteButtons(),
        ],
    ],

    // version control for css and js
    'pixelopen.asset-version.active' => true,

    // Locator 2.1 reads this default even with OpenStreetMap selected.
    // A string avoids its null array key deprecation on PHP 8.5.
    'sylvainjule.locator.mapbox.id' => 'mapbox/outdoors-v11',

    // for github pages: /studiya-kuhni/
    'jr.static_site_generator' => [
        'endpoint' => $environment === 'local' ? 'generate-static-site' : null,
        'output_folder' => './static',
        'base_url' => '/',
        'skip_media' => false,
    ],


    'cache' => [
        'pages' => [
            'active' => false,
            // Forms contain CSRF tokens; public HTML must not be shared in cache.
        ]
    ],

    'thumbs' => [
        'driver' => extension_loaded('imagick') ? 'imagick' : 'gd',
        'quality' => 82,
        'interlace' => true,
        'threads' => 1,
        'presets' => [
            'card' => [
                'width' => 960,
                'quality' => 82,
            ],
            'slide' => [
                'width' => 1600,
                'quality' => 82,
            ],
            'hero' => [
                'width' => 2200,
                'quality' => 84,
            ],
        ],
    ],

];
