<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';
require_once dirname(__DIR__) . '/site/plugins/studio/Environment.php';
require_once dirname(__DIR__) . '/site/plugins/studio-panel/Panel.php';

use Kirby\Cms\App;
use Kirby\Filesystem\Dir;
use Kirby\Panel\Assets;
use Kirby\Panel\Menu;
use Kirby\Panel\Panel as NativePanel;
use Studio\Panel;

$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/studio-panel-test-' . bin2hex(random_bytes(8));
mkdir($temporary . '/config', 0700, true);
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};

try {
    $kirby = new App([
        'roots' => [
            'index' => $root,
            'config' => $temporary . '/config',
            'content' => $temporary . '/content',
            'accounts' => $temporary . '/accounts',
            'sessions' => $temporary . '/sessions',
            'cache' => $temporary . '/cache',
            'media' => $temporary . '/media',
        ],
        'urls' => ['index' => 'https://studio.example.com'],
        'request' => ['url' => 'https://studio.example.com/panel', 'method' => 'GET', 'query' => ['_json' => 1]],
        'users' => [['id' => 'panel-fixture-user', 'email' => 'editor@example.com', 'role' => 'admin', 'language' => 'ru']],
        'options' => [
            'debug' => true,
            'studio.environment' => 'production',
            'studio.productionUrl' => 'https://studio.example.com',
            'studio.unpublishedPaths' => ['archive'],
            'sylvainjule.locator.mapbox.id' => 'mapbox/outdoors-v11',
            'panel.menu' => static fn (App $kirby): array => Panel::menu($kirby),
            'panel.viewButtons.page' => Panel::pageButtons(),
            'panel.viewButtons.site' => Panel::siteButtons(),
        ],
        'site' => [
            'content' => ['title' => 'Panel fixture'],
            'children' => [
                ['slug' => 'home', 'template' => 'home', 'content' => ['title' => 'Главная']],
                ['slug' => 'contacts', 'template' => 'contacts', 'content' => ['title' => 'Контакты']],
                ['slug' => 'faq', 'template' => 'faq', 'content' => ['title' => 'Вопросы']],
                ['slug' => 'fabrics', 'template' => 'fabrics', 'content' => ['title' => 'Фабрики'], 'children' => [
                    ['slug' => 'factory', 'template' => 'fabric', 'content' => ['title' => 'Factory fixture'], 'children' => [
                        ['slug' => 'kitchen', 'template' => 'kuhnya', 'content' => ['title' => 'Kitchen fixture']],
                    ]],
                ]],
            ],
        ],
    ]);
    $kirby->impersonate('kirby');
    $assert($kirby->system()->isInstalled(), 'In-memory user enables actual Panel routes without account writes');
    $assert(Panel::environment($kirby)['label'] === 'Основной сайт', 'Main origin clearly identified');

    $menu = Panel::menu($kirby);
    $assert($menu['site']['label'] === 'Страницы', 'Site entry retains familiar page navigation');
    $assert($menu['studio-fabrics']['link'] === '/pages/fabrics', 'Catalogue shortcut uses native Panel URL');
    $assert($menu['studio-contacts']['link'] === '/pages/contacts', 'Contact shortcut uses native Panel URL');
    foreach (['languages', 'users', 'system'] as $native) $assert(in_array($native, $menu, true), 'Native area retained: ' . $native);

    $kitchen = $kirby->page('fabrics/factory/kitchen');
    $assert($kitchen !== null, 'Kitchen fixture available without content files');
    $buttons = Panel::pageButtons();
    $assert(array_values(array_filter($buttons, 'is_string')) === ['open', 'preview', '-', 'settings', 'languages', 'status'], 'All native page controls preserved in original order');
    $assert(array_values(array_filter(Panel::siteButtons(), 'is_string')) === ['open', 'preview', 'languages'], 'All native site controls preserved');
    $parentButton = $buttons['studio-parent']($kitchen);
    $assert($parentButton['link'] === '/pages/fabrics+factory', 'Kitchen source link points to its own factory');
    $assert($buttons['studio-parent']($kirby->page('contacts')) === null, 'Unrelated pages have no misleading factory button');
    $renderedButtons = $kitchen->panel()->buttons();
    $assert(count($renderedButtons) >= 6, 'Kirby renders native and custom button definitions');
    $rendered = json_encode($renderedButtons, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $assert(str_contains($rendered, '/studio-guide') && str_contains($rendered, '/pages/fabrics+factory'), 'Rendered buttons contain real guide/source links');

    $guide = Panel::guide($kirby);
    $assert($guide['component'] === 'k-studio-guide-view', 'Guide uses registered native-shell component');
    $assert(count($guide['props']['links']) === 5, 'Guide links to pages, home, catalogue, contacts and FAQ');
    $updateHelp = array_values(array_filter($guide['props']['sections'], static fn (array $section): bool => $section['title'] === 'После обновления Панели'));
    $assert(count($updateHelp) === 1 && str_contains($updateHelp[0]['text'], 'скопируйте несохранённый текст') && str_contains($updateHelp[0]['text'], 'обновите страницу браузера') && str_contains($updateHelp[0]['text'], 'Переключение вкладок внутри Панели не перезагружает редактор'), 'Update guidance protects unsaved work before explaining browser reload');
    preg_match_all('/\bid="icon-([^"]+)"/', (new Assets())->icons(), $iconMatches);
    $assert(count($iconMatches[1]) > 100, 'Icon registry comes from the installed Kirby Panel sprite');
    $collectIcons = static function (array $values) use (&$collectIcons): array {
        $icons = [];
        foreach ($values as $key => $value) {
            if ($key === 'icon' && is_string($value)) $icons[] = $value;
            if (is_array($value)) $icons = [...$icons, ...$collectIcons($value)];
        }
        return array_values(array_unique($icons));
    };
    foreach ($collectIcons([$menu, $renderedButtons, $guide['props'], NativePanel::areas()['studio-guide']]) as $icon) {
        $assert(in_array($icon, $iconMatches[1], true), 'Menu, guide and buttons use a registered Kirby icon: ' . $icon);
    }
    $text = implode('', array_column($guide['props']['sections'], 'text'));
    foreach (['Черновик', 'Tiptap', 'Ctrl+K', 'Фабрика', 'Архив', 'пока не настроена'] as $term) {
        $assert(str_contains($text, $term), 'Guide explains ' . $term);
    }
    $routeResponse = NativePanel::router('studio-guide');
    $assert($routeResponse->code() === 200, 'Actual Panel guide route returns HTTP 200, including route closure binding');
    $routeData = json_decode($routeResponse->body(), true);
    $assert(($routeData['$view']['component'] ?? null) === 'k-studio-guide-view', 'Actual Fiber response loads guide component');
    $assert(($routeData['$view']['props']['title'] ?? null) === 'Как редактировать сайт', 'Actual Fiber response includes expected guide props');
    $assert($routeData['$view']['props']['environment'] === $guide['props']['environment'], 'Guide response includes complete environment props for its renderer');
    $assert($routeData['$view']['props']['links'] === $guide['props']['links'] && $routeData['$view']['props']['sections'] === $guide['props']['sections'], 'Guide response preserves every quick link and instructional section');
    $assert(!isset($routeData['$view']['error']), 'Guide route has no hidden server-side rendering error');

    foreach ([
        ['/panel/pages/fabrics', 'panel', '', 'Фабрики и кухни'],
        ['/panel/pages/fabrics+factory', 'panel', '', 'Фабрики и кухни'],
        ['/panel/pages/fabrics%2Bfactory%2Bkitchen', 'panel', '', 'Фабрики и кухни'],
        ['/panel/pages/fabrics+factory/files/photo.jpg', 'panel', '', 'Фабрики и кухни'],
        ['/panel/pages/contacts', 'panel', '', 'Контакты и форма'],
        ['/panel/pages/contacts/files/photo.jpg', 'panel', '', 'Контакты и форма'],
        ['/panel/pages/fabrics-extra', 'panel', '', 'Страницы'],
        ['/panel/pages/contacts-extra', 'panel', '', 'Страницы'],
        ['/panel/pages/home', 'panel', '', 'Страницы'],
        ['/panel/site', 'panel', '', 'Страницы'],
        ['/panel-extra/pages/fabrics', 'panel', '', 'Страницы'],
        ['/workspace/editor/pages/fabrics+factory', 'editor', '/workspace', 'Фабрики и кухни'],
        ['/workspace/editor/pages/contacts', 'editor', '/workspace', 'Контакты и форма'],
        ['/workspace/editor-extra/pages/fabrics', 'editor', '/workspace', 'Страницы'],
    ] as [$path, $slug, $base, $expected]) {
        $kirby->session()->commit();
        $kirby = $kirby->clone([
            'urls' => ['index' => 'https://studio.example.com' . $base],
            'options' => ['panel.slug' => $slug],
            'request' => ['url' => 'https://studio.example.com' . $path, 'method' => 'GET', 'query' => ['_json' => 1]],
        ]);
        $kirby->impersonate('kirby');
        $entries = (new Menu(NativePanel::areas(), [], 'site'))->entries();
        $current = array_values(array_filter($entries, static fn ($entry): bool => is_array($entry) && ($entry['current'] ?? false)));
        $assert(array_column($current, 'text') === [$expected], 'Exactly one correct native menu entry is current at ' . $path);
        $outside = (new Menu(NativePanel::areas(), [], 'users'))->entries();
        $assert(count(array_filter($outside, static fn ($entry): bool => is_array($entry) && ($entry['current'] ?? false) && in_array($entry['text'], ['Страницы', 'Фабрики и кухни', 'Контакты и форма'], true))) === 0, 'Page shortcuts never override another native area at ' . $path);
    }

    foreach (['pages/fabrics+factory' => 'Фабрики и кухни', 'pages/fabrics+factory+kitchen' => 'Фабрики и кухни', 'pages/contacts' => 'Контакты и форма'] as $path => $expected) {
        $kirby->session()->commit();
        $kirby = $kirby->clone([
            'urls' => ['index' => 'https://studio.example.com'],
            'options' => ['panel.slug' => 'panel'],
            'request' => ['url' => 'https://studio.example.com/panel/' . $path, 'method' => 'GET', 'query' => ['_json' => 1]],
        ]);
        $kirby->impersonate('kirby');
        $response = NativePanel::router($path);
        $data = json_decode($response->body(), true);
        $current = array_values(array_filter($data['$menu'] ?? [], static fn ($entry): bool => is_array($entry) && ($entry['current'] ?? false)));
        $assert($response->code() === 200 && array_column($current, 'text') === [$expected], 'Real Fiber route returns correct native highlight for ' . $path);
    }

    foreach ([['staging', 'studio.example.com', 'Дизайн-версия'], ['production', 'design.studio.example.com', 'Дизайн-версия'], ['local', 'localhost', 'Локальная версия'], ['production', 'wrong.example.com', 'Рабочая версия']] as [$environment, $host, $label]) {
        $kirby->session()->commit();
        $kirby = $kirby->clone([
            'options' => ['studio.environment' => $environment],
            'request' => ['url' => 'https://' . $host . '/panel', 'method' => 'GET'],
        ]);
        $assert(Panel::environment($kirby)['label'] === $label, 'Environment label is accurate on ' . $host);
    }

    $translations = Panel::translations();
    $tiptap = json_decode(file_get_contents($root . '/site/plugins/kirby-tiptap/translations/en.json'), true);
    $locator = require $root . '/site/plugins/locator/lib/languages/en.php';
    foreach ([...array_map(static fn ($key) => 'tiptap.' . $key, array_keys($tiptap)), ...array_keys($locator)] as $key) {
        $assert(isset($translations[$key]) && preg_match('/[А-Яа-яЁё]/u', $translations[$key]) === 1, 'Russian translation covers ' . $key);
    }
    $assert(!is_dir($temporary . '/content') || Dir::index($temporary . '/content') === [], 'Navigation and guide never create or change content files');
    $assert(!is_dir($temporary . '/accounts') || Dir::index($temporary . '/accounts') === [], 'Navigation and guide never create or change account files');
} finally {
    if (isset($kirby)) $kirby->session()->destroy();
    Dir::remove($temporary);
}

echo "Panel: {$checks} native navigation, guide, environment and Russian translation checks passed without content/account writes.\n";
