<?php

use Kirby\Cms\App;
use Studio\Panel;

require_once dirname(__DIR__) . '/studio/Environment.php';
require_once __DIR__ . '/Panel.php';

App::plugin('studio/panel', [
    'areas' => [
        'studio-guide' => static fn (App $kirby) => [
            'label' => 'Помощь',
            'icon' => 'question',
            'menu' => true,
            'views' => [
                [
                    'pattern' => 'studio-guide',
                    'action' => fn () => Panel::guide($kirby),
                ],
            ],
        ],
    ],
    'translations' => ['ru' => Panel::translations()],
]);
