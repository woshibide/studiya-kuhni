<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';
require_once dirname(__DIR__) . '/site/plugins/studio-seo/OgImage.php';

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Filesystem\Dir;
use Studio\Seo\Metadata;
use Studio\Seo\OgImage;

$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/studio-seo-' . bin2hex(random_bytes(8));
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
try {
    Dir::copy($root . '/content', $temporary . '/content');
    $kirby = new App([
        'roots' => ['index' => $root, 'content' => $temporary . '/content', 'cache' => $temporary . '/cache', 'sessions' => $temporary . '/sessions', 'media' => $temporary . '/media'],
        'urls' => ['index' => 'https://studio.example.com'],
        'routes' => OgImage::routes(),
        'options' => ['studio.productionUrl' => 'https://studio.example.com', 'studio.environment' => 'production'],
    ]);
    $kirby->impersonate('kirby');
    $site = $kirby->site()->update(['seo_title' => 'Студия Кухни', 'seo_description' => 'Описание сайта']);
    $page = $kirby->page('designers')->update(['seo_title' => '', 'seo_description' => '', 'seo_image' => '', 'seo_generate_image' => true, 'seo_image_mode' => 'cover']);
    $assert(Metadata::title($page) === (string)$page->title(), 'Empty title uses page title');
    $assert(Metadata::description($page) === 'Описание сайта', 'Empty description inherits site');
    $assert(Metadata::canonical($page) === 'https://studio.example.com/designers', 'Preview uses production canonical path');
    $assert(Metadata::canonical($site->homePage()) === 'https://studio.example.com', 'Home canonical has no /home segment');
    $preview = Metadata::preview($page);
    $assert($preview['siteTitle'] === 'Студия Кухни' && $preview['path'] === '/designers', 'Preview defaults match rendered metadata');
    $sitePreview = Metadata::preview($site);
    $assert($sitePreview['isSite'] && $sitePreview['pageTitle'] === 'Название страницы', 'Site preview explicitly uses a sample page');

    $before = $page->files()->count();
    $imageBefore = Metadata::image($page);
    $response = $kirby->render('designers/og-image');
    $assert($response->code() === 200 && $response->type() === 'image/png', 'Actual OG route uses guarded renderer');
    $png = $response->body();
    $dimensions = getimagesizefromstring($png);
    $assert($dimensions[0] === 1200 && $dimensions[1] === 630, 'OG output is 1200 × 630');
    $assert($page->files()->count() === $before, 'OG generation creates no editorial files');
    $assert(OgImage::render(Metadata::title($page), Metadata::siteTitle($site)) === $png, 'API and public renderer generate identical cards');
    $assert($kirby->user()->isKirby(), 'Renderer restores caller identity');
    $page = $page->update(['seo_title' => '  Кухни для дизайнеров & архитекторов  ', 'seo_description' => "Строка\nописания <тест>"]);
    $assert(Metadata::title($page) === 'Кухни для дизайнеров & архитекторов', 'Whitespace normalization agrees with preview');
    $assert(Metadata::description($page) === 'Строка описания <тест>', 'Description is plain text with normalized whitespace');
    $assert(Metadata::image($page)['url'] !== $imageBefore['url'], 'Title edit invalidates generated image URL');
    $assert(OgImage::render(Metadata::title($page), Metadata::siteTitle($site)) !== $png, 'Title edit changes generated pixels');
    $oldRevision = OgImage::revision($page);
    $kirby->setSite($site = $site->update(['seo_title' => 'Новое имя студии']));
    $assert(OgImage::revision($page) === $oldRevision, 'Site name edit leaves fixed logo artwork unchanged');

    foreach (['Кухни для дизайнеров и архитекторов', str_repeat('Оченьдлинноесловобезпробелов', 20), str_repeat('Кухни для жизни ', 60)] as $title) {
        $wrapped = OgImage::wrap($title);
        $assert(mb_check_encoding($wrapped, 'UTF-8'), 'Wrapping preserves Cyrillic characters');
        $lines = explode("\n", $wrapped);
        $assert(count($lines) <= 4, 'Long titles fit at most four lines');
        foreach ($lines as $line) {
            $bounds = imagettfbbox(48, 0, OgImage::options()['font.path'], $line);
            $assert($bounds[2] - $bounds[0] <= 1056, 'Title line fits template width');
        }
    }

    $layout = OgImage::layout("Aster Cucine,\nAtelier");
    $assert($layout['size'] === 112 && count($layout['lines']) === 2, 'Reference title uses two explicit lines at 112 pixels');
    $assert($layout['x'] === 40 && $layout['y'] === 72, 'Reference title preserves intended inset');
    foreach (['Кухни для дизайнеров и архитекторов', str_repeat('Длинноеслово', 40), str_repeat("Строка\n", 40)] as $title) {
        foreach ([['size' => 144, 'x' => 1152, 'y' => 296], ['size' => -1, 'x' => -500, 'y' => -500]] as $settings) {
            $layout = OgImage::layout($title, $settings);
            $assert($layout['x'] >= 24 && $layout['x'] + $layout['width'] <= 1176, 'Text respects horizontal safe area');
            $assert($layout['y'] >= 24 && $layout['y'] + $layout['height'] <= 320, 'Text never overlaps logo');
        }
    }
    $oldRevision = OgImage::revision($page);
    $page = $page->update(['seo_og_size' => 80, 'seo_og_x' => 65, 'seo_og_y' => 90, 'seo_og_title' => "Своя обложка\nВторая строка"]);
    $assert(OgImage::revision($page) !== $oldRevision, 'Cover typography changes invalidate public URL');
    $response = OgImage::response('designers/og-image');
    $assert($response->body() === OgImage::render(Metadata::coverTitle($page), '', OgImage::settings($page)), 'Saved typography matches public image exactly');
    $canvas = imagecreatefromstring($response->body());
    $template = imagecreatefromjpeg($root . '/assets/og/og-image_OG combined.jpg');
    for ($y = 330; $y < 630; $y += 7) {
        for ($x = 0; $x < 1200; $x += 7) {
            $assert(imagecolorat($canvas, $x, $y) === imagecolorat($template, $x, $y), 'Supplied logo remains pixel-identical');
        }
    }
    $page = $page->update(['seo_image_mode' => 'shared']);
    $assert(Metadata::image($page)['url'] === Metadata::fallback()['url'], 'Disabled generation uses supplied fallback artwork');
    $assert(Metadata::preview($site)['fallbackImage']['url'] === Metadata::fallback()['url'], 'Site preview uses same fallback');

    $uploaded = $page->createFile(['source' => $root . '/assets/og/template.png', 'filename' => 'custom-share.png', 'template' => 'social-image', 'content' => ['alt' => 'Своя обложка']]);
    $page = $page->update(['seo_image_mode' => 'custom', 'seo_image' => [$uploaded->uuid()->toString()]]);
    $assert(Metadata::image($page)['url'] === $uploaded->url(), 'Own image takes precedence');
    $assert(Metadata::image($page)['alt'] === 'Своя обложка', 'Own image retains accessible description');
    $form = Kirby\Form\Fields::for($page);
    $legacyModes = [
        [['seo_generate_image' => 'false', 'seo_image' => [$uploaded->uuid()->toString()]], 'custom'],
        [['seo_generate_image' => 'true'], 'cover'],
        [['seo_generate_image' => 'false', 'seo_image' => ''], 'shared'],
        [[], 'cover'],
        [['seo_image_mode' => 'shared', 'seo_generate_image' => 'true', 'seo_image' => [$uploaded->uuid()->toString()]], 'shared'],
    ];
    foreach ($legacyModes as [$content, $expected]) {
        $values = $form->reset()->fill($content)->toFormValues();
        $assert($values['seo_image_mode'] === $expected, 'Panel normalizes legacy mode without leaking previous content version');
    }
    $page = $page->update(['seo_image_mode' => 'shared']);
    $assert(Metadata::image($page)['url'] === Metadata::fallback()['url'], 'Shared mode ignores retained custom file');
    $page = $page->update(['seo_image_mode' => 'custom']);
    $assert(Metadata::image($page)['url'] === $uploaded->url(), 'Returning to custom mode preserves selected image');
    $page = $page->update(['seo_image_mode' => 'cover']);
    $assert(str_contains(Metadata::image($page)['url'], '/og-image?'), 'Automatic cover can override selected image');
    $page = $page->update(['seo_image_mode' => 'shared', 'seo_image' => '']);
    $shared = $site->createFile(['source' => $root . '/assets/og/template.png', 'filename' => 'site-share.png']);
    $kirby->setSite($site = $site->update(['seo_image' => [$shared->uuid()->toString()]]));
    $assert(Metadata::image($page)['url'] === $shared->url(), 'Empty image inherits site image');
    $assert(OgImage::response('designers/og-image')->code() === 302, 'Manual image route redirects with the real image MIME type');

    $draft = Page::create(['slug' => 'private-seo', 'template' => 'default', 'content' => ['title' => 'Private']]);
    foreach (['private-seo/og-image', 'archive/og-image', 'archive/kitchen-test-2/og-image', 'error/og-image', 'missing/og-image'] as $path) {
        $assert(OgImage::response($path)->code() === 404, 'Nonpublic OG content stays inaccessible: ' . $path);
    }
    echo "SEO: {$checks} metadata, image generation, Unicode, cache, fallback and visibility checks passed.\n";
} finally {
    if (isset($kirby)) $kirby->session()->destroy();
    Dir::remove($temporary);
}
