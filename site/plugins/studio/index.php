<?php

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Http\Response;
use Studio\Environment;

require_once __DIR__ . '/Environment.php';
require_once __DIR__ . '/Location.php';
require_once __DIR__ . '/RichText.php';

function studio_indexable(): bool
{
    $kirby = App::instance();
    return Environment::indexable(
        $kirby->option('studio.environment', 'local'),
        $kirby->option('studio.productionUrl', ''),
        $kirby->request()->url()->toString()
    );
}

function studio_asset_url(string $path): string
{
    $url = relative_url($path);
    $file = App::instance()->root('index') . '/' . ltrim($path, '/');
    return is_file($file) ? $url . '?v=' . filemtime($file) : $url;
}

App::plugin('studio/site', [
    'fields' => [
        'studio-tiptap' => [
            'extends' => 'tiptap',
            'computed' => [
                'value' => function () {
                    return Studio\RichText::panelValue((string)($this->value ?? ''), $this->model(), (bool)$this->inline());
                },
            ],
        ],
    ],
    'fieldMethods' => [
        'studioText' => function ($field, bool $inline = false, int $headingLevel = 2): string {
            return Studio\RichText::render($field, $inline, $headingLevel);
        },
        'studioPlainText' => function ($field, int $limit = 0): string {
            return Studio\RichText::plain($field, $limit);
        },
    ],
    'pageMethods' => [
        'studioKitchenImages' => function (): Kirby\Cms\Files {
            $selected = $this->kitchen_gallery_images()->toFiles()->filterBy('type', 'image');
            if ($selected->isNotEmpty()) return $selected;
            return $this->images()->sorted()->filter(static fn ($image) => strtolower($image->extension()) !== 'svg');
        },
        'studioMapLocation' => function (): ?array {
            $source = $this->intendedTemplate()->name() === 'kuhnya' ? $this->parent() : $this;
            if (!$source) return null;
            return Studio\Location::resolve(
                $source->fabric_location()->value(),
                $source->fabric_map_lat()->value(),
                $source->fabric_map_lng()->value(),
                $source->fabric_map_zoom()->value(),
                (string)$source->fabric_map_label()->or($source->title())->value(),
                $source->content()->has('fabric_location')
            );
        },
        'studioPubliclyVisible' => function (): bool {
            if ($this->isDraft() || $this->isErrorPage()) return false;
            foreach ($this->parents() as $parent) {
                if ($parent->isDraft()) return false;
            }
            foreach ($this->kirby()->option('studio.unpublishedPaths', []) as $path) {
                if ($this->id() === $path || str_starts_with($this->id(), $path . '/')) return false;
            }
            return true;
        },
    ],
    'hooks' => [
        'route:before' => function ($route, $path, $method) {
            if (!studio_indexable() || preg_match('~^(panel|api)(/|$)~', $path)) {
                $this->response()->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
            }
        },
        'route:after' => function ($route, $path, $method, $result, $final) {
            if ($result instanceof Page && !$result->studioPubliclyVisible() && !$result->isErrorPage()) {
                if (!$this->user() || $this->option('studio.environment') === 'production') {
                    return $this->site()->errorPage();
                }
            }
            return $result;
        },
    ],
    'routes' => [
        [
            'pattern' => 'robots.txt',
            'action' => function () {
                if (!studio_indexable()) return new Response("User-agent: *\nDisallow: /\n", 'text/plain');
                $origin = rtrim(option('studio.productionUrl'), '/');
                return new Response("User-agent: *\nAllow: /\nDisallow: /panel\nDisallow: /api\nSitemap: {$origin}/sitemap.xml\n", 'text/plain');
            },
        ],
        [
            'pattern' => 'sitemap.xml',
            'action' => function () {
                if (!studio_indexable()) return new Response('', 'application/xml', 404);
                $origin = rtrim(option('studio.productionUrl'), '/');
                $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
                foreach (site()->index() as $entry) {
                    if (!$entry->studioPubliclyVisible() || $entry->seo_noindex()->toBool()) continue;
                    $path = $entry->isHomePage() ? '' : '/' . $entry->uri();
                    $xml .= '<url><loc>' . htmlspecialchars($origin . $path, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>';
                }
                return new Response($xml . '</urlset>', 'application/xml');
            },
        ],
    ],
]);
