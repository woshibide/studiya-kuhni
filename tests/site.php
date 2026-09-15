<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';
require_once dirname(__DIR__) . '/site/plugins/studio/Environment.php';

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Filesystem\Dir;
use Studio\Environment;

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$main = 'https://studio.example.com';
$assert(Environment::indexable('production', $main, $main . '/fabrics'), 'Main production host indexable');
foreach ([
    ['local', $main, $main], ['staging', $main, $main],
    ['production', '', $main], ['production', $main, 'https://design.studio.example.com'],
    ['production', 'https://design.studio.example.com', 'https://design.studio.example.com'],
    ['production', $main, 'https://studio.example.com.attacker.test'],
    ['production', $main, 'http://studio.example.com'],
    ['production', $main, 'https://studio.example.com:8443'],
    ['production', 'http://studio.example.com', $main],
    ['production', $main . '/path', $main],
    ['production', 'https://user:password@studio.example.com', $main],
] as $case) $assert(!Environment::indexable(...$case), 'Unsafe or non-production origin cannot index: ' . $case[2]);

$environment = $argv[1] ?? 'local';
$host = $argv[2] ?? 'localhost';
$indexable = $environment === 'production' && $host === 'studio.example.com';
$temporary = sys_get_temp_dir() . '/studio-site-test-' . bin2hex(random_bytes(6));
mkdir($temporary . '/config', 0700, true);
try {
    Dir::copy(dirname(__DIR__) . '/content', $temporary . '/content');
    mkdir($temporary . '/content/_drafts/private-fixture', 0700, true);
    file_put_contents($temporary . '/content/_drafts/private-fixture/default.txt', "Title: Private fixture\n\n----\n\nText: Never publish\n");
    mkdir($temporary . '/content/noindex-fixture', 0700, true);
    file_put_contents($temporary . '/content/noindex-fixture/default.txt', "Title: Noindex fixture\n\n----\n\nSeo_noindex: true\n");
    $kirby = new App([
        'roots' => [
            'index' => dirname(__DIR__), 'config' => $temporary . '/config',
            'content' => $temporary . '/content', 'media' => $temporary . '/media',
            'cache' => $temporary . '/cache', 'sessions' => $temporary . '/sessions',
        ],
        'urls' => ['index' => 'https://' . $host],
        'request' => ['url' => 'https://' . $host . '/', 'method' => 'GET'],
        'options' => [
            'debug' => true, 'studio.environment' => $environment,
            'studio.productionUrl' => $main, 'studio.unpublishedPaths' => ['archive'],
        ],
    ]);
    $assert(studio_indexable() === $indexable, 'Runtime uses actual request hostname');
    $assert($kirby->site()->find('fabrics')->intendedTemplate()->name() === 'fabrics', 'Catalogue template not shadowed by orphan metadata');
    $draft = $kirby->site()->draft('private-fixture');
    $assert($draft && !$draft->studioPubliclyVisible(), 'Draft excluded from public collections');
    $assert(!$kirby->site()->find('archive/kitchen-test-2')->studioPubliclyVisible(), 'Hidden archive descendants excluded');
    $assert($kirby->site()->find('fabrics/aran-cucine')->studioPubliclyVisible(), 'Published unlisted manufacturers remain visible');

    // Each simulated HTTP request needs a fresh responder; status and headers must not leak between requests.
    $render = static function (string $path) use (&$kirby, $host) {
        $kirby->session()->commit();
        $kirby = $kirby->clone([
            'path' => $path,
            'request' => ['url' => 'https://' . $host . '/' . $path, 'method' => 'GET'],
        ]);
        return $kirby->render($path);
    };

    $robots = $render('robots.txt');
    $assert($robots->code() === 200, 'robots.txt responds successfully');
    $assert(str_contains($robots->body(), $indexable ? 'Sitemap: ' . $main : 'Disallow: /'), 'Robots matches environment');
    $assert(str_contains((string)$robots->header('X-Robots-Tag'), 'noindex') === !$indexable, 'Robots response header matches environment');
    $sitemap = $render('sitemap.xml');
    $assert($sitemap->code() === ($indexable ? 200 : 404), 'Staging never serves production sitemap');
    $assert(str_contains((string)$sitemap->header('X-Robots-Tag'), 'noindex') === !$indexable, 'Sitemap response header matches environment even on 404');
    if ($indexable) {
        $assert(str_contains($sitemap->body(), $main . '/fabrics'), 'Production sitemap contains canonical catalogue');
        $assert(!str_contains($sitemap->body(), '/archive') && !str_contains($sitemap->body(), 'private-fixture'), 'Sitemap excludes private content');
        $assert(!str_contains($sitemap->body(), '/noindex-fixture'), 'Sitemap excludes published pages marked noindex');
    }

    foreach (['unknown-route-fixture', 'archive', 'archive/kitchen-test-2', 'private-fixture'] as $path) {
        $response = $render($path);
        $assert($response->code() === 404, $path . ': anonymous HTTP request returns 404');
        $assert(str_contains((string)$response->header('X-Robots-Tag'), 'noindex'), $path . ': error response has noindex header');
    }
    $publicResponse = $render('contacts');
    $assert($publicResponse->code() === 200, 'Published contact route returns HTTP 200');
    $assert(str_contains((string)$publicResponse->header('X-Robots-Tag'), 'noindex') === !$indexable, 'Published contact response header matches environment');
    $noindexResponse = $render('noindex-fixture');
    $assert($noindexResponse->code() === 200, 'Page-level noindex does not hide published page');
    $assert(str_contains((string)$noindexResponse->header('X-Robots-Tag'), 'noindex'), 'Page-level noindex sets HTTP header on main and preview hosts');
    $noindexDom = new DOMDocument();
    @$noindexDom->loadHTML('<?xml encoding="UTF-8">' . $noindexResponse->body());
    $noindexXpath = new DOMXPath($noindexDom);
    $assert(str_contains($noindexXpath->evaluate('string(//meta[@name="robots"]/@content)'), 'noindex'), 'Page-level noindex sets robots meta');

    $pageIds = $environment === 'local'
        ? $kirby->site()->index()->filter('studioPubliclyVisible', true)->pluck('id')
        : ['home', 'contacts', 'privacy', 'fabrics', 'fabrics/aran-cucine/lab13'];
    foreach ($pageIds as $id) {
        $page = $kirby->site()->find($id);
        $html = $page->render();
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        if ($id === 'fabrics') {
            $photoLinks = $xpath->query('//a[@data-gallery-catalog-open]');
            $galleryKeys = [];
            foreach ($xpath->query('//*[@data-gallery-embedded="true"]') as $gallery) {
                $assert($xpath->query('.//*[@data-gallery-open]', $gallery)->length === 0, 'Catalogue reuses the overlay without duplicate inline photos');
                foreach ($xpath->query('.//*[@data-gallery-key]', $gallery) as $thumbnail) {
                    $key = $thumbnail->getAttribute('data-gallery-key');
                    $assert(!isset($galleryKeys[$key]), 'Catalogue photo keys are unique across kitchens');
                    $galleryKeys[$key] = true;
                }
            }
            $assert($photoLinks->length > 0 && $photoLinks->length === count($galleryKeys), 'Every catalogue photo has exactly one gallery entry');
            foreach ($photoLinks as $photoLink) {
                $key = $photoLink->getAttribute('data-gallery-catalog-open');
                $assert(isset($galleryKeys[$key]), 'Catalogue photo opens its matching gallery image');
                $href = $photoLink->getAttribute('href');
                $target = $kirby->site()->find(ltrim((string)parse_url($href, PHP_URL_PATH), '/'));
                parse_str((string)parse_url($href, PHP_URL_QUERY), $query);
                $assert($target && $target->studioPubliclyVisible() && $target->file($query['gallery'] ?? '')?->id() === $key, 'Photo fallback links to the correct published kitchen and image');
            }
            foreach ($xpath->query('//h2[contains(@class,"fabric-grid__fabric-name")]/a | //h3[contains(@class,"fabric-grid__title")]/a') as $nameLink) {
                $target = $kirby->site()->find(ltrim($nameLink->getAttribute('href'), '/'));
                $assert($target && $target->studioPubliclyVisible(), 'Factory and kitchen names link to published pages');
                $assert(str_contains($nameLink->getAttribute('class'), 'hover-underline'), 'Catalogue names use the shared underline');
                $assert(str_contains($nameLink->getAttribute('class'), 'internal-link') === ($target->intendedTemplate()->name() === 'kuhnya'), 'Only kitchen names display an arrow');
            }
        }
        $assert($xpath->evaluate('string(//html/@lang)') === 'ru', $id . ': Russian language declared');
        $assert($xpath->query('//h1')->length === 1, $id . ': one H1');
        $assert($xpath->query('//main[@id="main-content"]')->length === 1, $id . ': skip target present');
        $assert($xpath->query('//a[@href="#main-content"]')->length === 1, $id . ': skip navigation present');
        $robotsValue = $xpath->evaluate('string(//meta[@name="robots"]/@content)');
        $assert(str_contains($robotsValue, $indexable ? 'index, follow' : 'noindex'), $id . ': robots meta correct');
        foreach ($xpath->query('//img') as $img) {
            $assert($img->hasAttribute('alt'), $id . ': image accessible name specified');
            $assert((float)$img->getAttribute('width') > 0 && (float)$img->getAttribute('height') > 0, $id . ': image dimensions present: ' . $img->getAttribute('src'));
        }
        $seen = [];
        foreach ($xpath->query('//*[@id]') as $element) {
            $value = $element->getAttribute('id');
            $assert(!isset($seen[$value]), $id . ': unique id ' . $value);
            $seen[$value] = true;
        }
    }
} finally {
    if (isset($kirby)) $kirby->session()->destroy();
    Dir::remove($temporary);
}
echo "Site {$environment}/{$host}: {$checks} checks passed.\n";
