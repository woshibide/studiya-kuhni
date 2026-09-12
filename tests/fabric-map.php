<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Filesystem\Dir;

function relative_url(string $path): string
{
    return $path;
}

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$temporary = sys_get_temp_dir() . '/studio-map-test-' . bin2hex(random_bytes(6));
mkdir($temporary . '/config', 0700, true);
try {
    $kirby = new App([
        'roots' => [
            'index' => dirname(__DIR__), 'config' => $temporary . '/config',
            'media' => $temporary . '/media', 'cache' => $temporary . '/cache',
            'sessions' => $temporary . '/sessions',
        ],
        'urls' => ['index' => 'https://studio.example.com'],
        'options' => ['debug' => true, 'studio.environment' => 'local'],
    ]);
    $render = static function (Page $page): array {
        $html = snippet('fabric-info', ['page' => $page], true);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        return [$html, new DOMXPath($dom)];
    };
    $fixture = static function (array $content, ?Page $parent = null, string $template = 'fabric'): Page {
        return Page::factory([
            'slug' => 'map-fixture', 'template' => $template, 'parent' => $parent,
            'content' => array_merge([
                'title' => 'Fixture', 'fabric_info_text' => 'Factory information',
                'fabric_logo' => '',
            ], $content),
        ]);
    };

    foreach (['aran-cucine', 'aster-cucine', 'home-cucine', 'scavolini'] as $slug) {
        $source = $kirby->site()->find('fabrics/' . $slug);
        $assert($source instanceof Page, $slug . ': source exists');
        $location = $source->studioMapLocation();
        $assert($location !== null, $slug . ': existing coordinates resolve');
        $page = $fixture(array_merge($source->content()->toArray(), ['fabric_logo' => '']));
        [$html, $xpath] = $render($page);
        $assert($xpath->query('//*[@data-fabric-map]')->length === 1, $slug . ': interactive map rendered');
        $assert((float)$xpath->evaluate('string(//*[@data-fabric-map]/@data-lat)') === $location['lat'], $slug . ': latitude preserved');
        $assert((float)$xpath->evaluate('string(//*[@data-fabric-map]/@data-lng)') === $location['lng'], $slug . ': longitude preserved');
        $assert($xpath->query('//a[contains(@href,"www.openstreetmap.org/?mlat=")]')->length === 1, $slug . ': no-JS external map available');
        $assert(!str_contains($html, 'assets/map.png'), $slug . ': obsolete photo removed');
    }

    $mossman = $kirby->site()->find('fabrics/mossman');
    $assert($mossman->studioMapLocation() === null, 'Missing Mossman location does not become Moscow');
    [$html, $xpath] = $render($fixture(array_merge($mossman->content()->toArray(), ['fabric_logo' => ''])));
    $assert($xpath->query('//*[@data-fabric-map]')->length === 0, 'Missing location creates no map or tile requests');
    $assert(str_contains($html, 'Расположение фабрики пока не указано.'), 'Missing location has clear empty state');
    $assert(!str_contains($html, '55.709744') && !str_contains($html, '37.592538'), 'No invented Moscow coordinates');

    $parent = $fixture([
        'fabric_location' => "lat: 45.1\nlon: 12.2\nzoom: 10",
        'fabric_map_lat' => '55.709744', 'fabric_map_lng' => '37.592538',
        'fabric_map_label' => '<script>alert(1)</script>', 'fabric_map_enabled' => 'false',
    ]);
    [$mapOnlyHtml, $mapOnlyXpath] = $render($fixture(['fabric_info_text' => '', 'fabric_location' => "lat: 45.1\nlon: 12.2"]));
    $assert($mapOnlyXpath->query('//*[@data-fabric-map]')->length === 1, 'Map does not depend on unfinished factory text');
    $kitchen = $fixture([], $parent, 'kuhnya');
    [$html, $xpath] = $render($kitchen);
    $assert((float)$xpath->evaluate('string(//*[@data-fabric-map]/@data-lat)') === 45.1, 'Kitchen inherits Locator latitude from factory');
    $assert((float)$xpath->evaluate('string(//*[@data-fabric-map]/@data-lng)') === 12.2, 'Locator longitude overrides legacy value');
    $assert($xpath->evaluate('string(//*[@data-fabric-map]/@data-zoom)') === '10', 'Locator zoom reaches map');
    $assert($xpath->query('//script')->length === 0, 'Map labels never inject markup');
    $assert($xpath->query('//*[@data-fabric-map]')->length === 1, 'Obsolete image toggle cannot suppress real location');
    $assert(str_contains($xpath->evaluate('string(//*[@data-fabric-map]/@data-attribution)'), 'cloudless.eox.at/'), 'EOX attribution supplied to renderer');
    $assert($xpath->query('//*[@role="status"]')->length === 1, 'Map status exposes polite live updates');

    $config = require dirname(__DIR__) . '/site/config/config.php';
    $assert(str_contains($config['studio.map.tileUrl'], '/s2cloudless_3857/default/g/{z}/{y}/{x}.jpg'), 'EOX 2016 uses WMTS row/column ordering');
    $assert(!str_contains($config['studio.map.tileUrl'], '?'), 'Public imagery needs no API key');
    $assert(str_contains($config['studio.map.attribution'], 'Copernicus Sentinel data 2016'), 'Imagery credits identify source and year');
    $assert($config['studio.map.maxZoom'] === 14, 'Zoom capped for Sentinel resolution');
    $assert($xpath->evaluate('string(//*[@data-fabric-map]/@data-max-zoom)') === '14', 'Provider zoom limit reaches renderer');

} finally {
    if (isset($kirby)) $kirby->session()->destroy();
    Dir::remove($temporary);
}
echo "Map rendering: {$checks} checks passed.\n";
