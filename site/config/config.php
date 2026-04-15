<?php

ini_set('memory_limit', '512M');

return [
    
    'debug' => true,
    'users' => [
        'admin' => [
            'email' => 'test@test.test',
            'password' => '1q2w3e4r', 
        ],
    ],


    // version control for css and js
    'pixelopen.asset-version.active' => true,

    'jr.static_site_generator' => [
        'endpoint' => 'generate-static-site',
        'output_folder' => './static',
        'base_url' => '/studiya-kuhni/',
        'skip_media' => false,
    ],


    'cache' => [
        'pages' => [
            'active' => false,
            'type'   => 'apcu'
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

    // 'johannschopplich.locked-pages' => [
    //     'slug' => 'HZIYKIcigTmbDQ6',
    //     'title' => 'Вход в кабинет',
    //     'error' => [
    //         'csrf' => 'Неверный CSRF-токен.',
    //         'password' => 'Неверный пароль.'
    //     ]
    // ],
    
    // 'tobimori.seo.canonicalBase' => 'https://www.luxor-kmv.ru',
    // 'tobimori.seo.lang' => 'ru_RU',

    // 'panel' => [
    //     'slug' => 'nRu1pn4T1m5LYmm',
    //     'favicon' => 'assets/favicons/favicon.svg'
    // ],

    // 'hooks' => [
    //     'page.update:after' => function ($newPage, $oldPage) {
    //         kirby()->cache('pages')->flush();
    //         kirby()->cache('hero-carousel')->flush();
    //         kirby()->cache('home-about-brand')->flush();
    //         kirby()->cache('home-collection')->flush();
    //         kirby()->cache('home-brands')->flush();
    //     },
    //     'page.create:after' => function ($page) {
    //         kirby()->cache('pages')->flush();
    //         kirby()->cache('hero-carousel')->flush();
    //         kirby()->cache('home-about-brand')->flush();
    //         kirby()->cache('home-collection')->flush();
    //         kirby()->cache('home-brands')->flush();
    //     },
    //     'page.delete:after' => function ($page) {
    //         kirby()->cache('pages')->flush();
    //         kirby()->cache('hero-carousel')->flush();
    //         kirby()->cache('home-about-brand')->flush();
    //         kirby()->cache('home-collection')->flush();
    //         kirby()->cache('home-brands')->flush();
    //     },
    //     'file.create:after' => function ($file) {
    //         kirby()->cache('pages')->flush();
    //         kirby()->cache('hero-carousel')->flush();
    //         kirby()->cache('home-about-brand')->flush();
    //         kirby()->cache('home-collection')->flush();
    //         kirby()->cache('home-brands')->flush();
    //     },
    //     'file.update:after' => function ($newFile, $oldFile) {
    //         kirby()->cache('pages')->flush();
    //         kirby()->cache('hero-carousel')->flush();
    //         kirby()->cache('home-about-brand')->flush();
    //         kirby()->cache('home-collection')->flush();
    //         kirby()->cache('home-brands')->flush();
    //     },
    //     'file.delete:after' => function ($file) {
    //         kirby()->cache('pages')->flush();
    //         kirby()->cache('hero-carousel')->flush();
    //         kirby()->cache('home-about-brand')->flush();
    //         kirby()->cache('home-collection')->flush();
    //         kirby()->cache('home-brands')->flush();
    //     }
    // ],

];