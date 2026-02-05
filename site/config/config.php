<?php

ini_set('memory_limit', '512M');

return [
    'debug' => true,
    'cache' => [
        'pages' => [
            'active' => false,
            'type'   => 'apcu',
            'ignore' => function ($page) {
                // requests page is not to be cached, but here it doesnt exist yet
                return $page->isHomePage() || $page->uid() === 'requests';
            }
        ]
    ],

    // 'thumbs' => [
    //     'driver' => 'imagick',
    //     'memory'  => '128M',   
    // ],

    // 'tobimori.thumbhash' => [
    //     'engine' => 'imagick',
    //     'sampleMaxSize' => 80,
    //     'blurRadius' => 1,
    // ],

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