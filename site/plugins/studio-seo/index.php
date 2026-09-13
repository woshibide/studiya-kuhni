<?php

use Kirby\Cms\App;
use Studio\Seo\Metadata;
use Studio\Seo\OgImage;

require_once __DIR__ . '/Metadata.php';
require_once __DIR__ . '/OgImage.php';
require_once __DIR__ . '/ImageModeField.php';

App::plugin('studio/seo', [
    'fields' => ['studio-image-mode' => Studio\Seo\ImageModeField::class],
    'sections' => [
        'studio-search-preview' => [
            'extends' => 'serp-preview',
            'computed' => [
                'studio' => function () { return Metadata::preview($this->model()); },
                'siteTitle' => function () { return Metadata::siteTitle($this->kirby()->site()); },
                'siteUrl' => function () { return $this->kirby()->option('studio.productionUrl') ?: $this->kirby()->url(); },
                'config' => fn () => ['formatters' => ['title' => false, 'description' => false]],
            ],
        ],
        'studio-sharing-preview' => [
            'computed' => ['studio' => function () { return Metadata::preview($this->model()); }],
        ],
    ],
    'api' => ['routes' => [
        [
            'pattern' => 'studio-seo/og-preview',
            'method' => 'POST',
            'action' => function () {
                $title = OgImage::text((string)get('title', ''));
                $settings = get('settings', []);
                $settings = is_array($settings) ? $settings : [];
                $siteTitle = Metadata::text(get('siteTitle', ''));
                return [
                    'url' => 'data:image/png;base64,' . base64_encode(OgImage::render($title, $siteTitle, $settings)),
                    'layout' => OgImage::layout($title, $settings),
                ];
            },
        ],
    ]],
]);
