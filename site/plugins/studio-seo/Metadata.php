<?php

namespace Studio\Seo;

use Kirby\Cms\Page;
use Kirby\Cms\Site;

final class Metadata
{
    public static function text(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string)$value) ?? '');
    }

    public static function siteTitle(Site $site): string
    {
        return self::text($site->seo_title()) ?: self::text($site->title());
    }

    public static function title(Page $page): string
    {
        return self::text($page->seo_title()) ?: self::text($page->title());
    }

    public static function coverTitle(Page $page): string
    {
        if ($page->seo_og_title()->isNotEmpty()) return OgImage::text($page->seo_og_title()->value());
        if ($page->seo_title()->isNotEmpty()) return self::title($page);
        if ($page->intendedTemplate()->name() === 'kuhnya' && $page->parent() instanceof Page) {
            return self::text($page->parent()->title()) . ",\n" . self::title($page);
        }
        return self::title($page);
    }

    public static function fallback(): array
    {
        return [
            'url' => url('assets/og/og-image_OG%20Fallback.jpg'),
            'alt' => 'Студия Кухни. Пятигорск, ул. Ермолова 14, ТЦ «Palazzo»',
            'width' => 1200, 'height' => 630, 'generated' => false,
        ];
    }

    public static function description(Page $page): string
    {
        return self::text($page->seo_description()) ?: self::text($page->kirby()->site()->seo_description());
    }

    public static function canonical(Page $page): string
    {
        $origin = $page->kirby()->option('studio.productionUrl', '');
        return $origin === '' ? '' : rtrim($origin, '/') . ($page->isHomePage() ? '' : '/' . $page->uri());
    }

    public static function resolveImageMode(mixed $mode, mixed $legacyGenerate, bool $hasImage): string
    {
        if (in_array($mode, ['cover', 'custom', 'shared'], true)) return $mode;
        if ($legacyGenerate === null || $legacyGenerate === '' || $legacyGenerate === true || $legacyGenerate === 'true') return 'cover';
        return $hasImage ? 'custom' : 'shared';
    }

    public static function imageMode(Page $page): string
    {
        return self::resolveImageMode($page->seo_image_mode()->value(), $page->seo_generate_image()->value(), $page->seo_image()->toFile() !== null);
    }

    public static function image(Page $page): array
    {
        $mode = self::imageMode($page);
        if ($mode === 'cover') {
            return ['url' => $page->url() . '/og-image?v=' . OgImage::revision($page), 'alt' => Metadata::text(self::coverTitle($page)), 'width' => 1200, 'height' => 630, 'generated' => true];
        }
        $file = ($mode === 'custom' ? $page->seo_image()->toFile() : null) ?? $page->kirby()->site()->seo_image()->toFile();
        if ($file) {
            return ['url' => $file->url(), 'alt' => (string)$file->alt(), 'width' => $file->width(), 'height' => $file->height(), 'generated' => false];
        }
        return self::fallback();
    }

    public static function preview(Page|Site $model): array
    {
        $isSite = $model instanceof Site;
        $site = $model->kirby()->site();
        $image = $site->seo_image()->toFile();
        return [
            'isSite' => $isSite,
            'pageTitle' => $isSite ? 'Название страницы' : self::text($model->title()),
            'coverTitle' => $isSite ? '' : self::coverTitle($model),
            'coverDefault' => !$isSite && $model->intendedTemplate()->name() === 'kuhnya' && $model->parent() instanceof Page ? self::text($model->parent()->title()) . ",\n" . self::text($model->title()) : ($isSite ? '' : self::text($model->title())),
            'fallbackImage' => self::fallback(),
            'templateImage' => url('assets/og/template.png'),
            'siteTitle' => self::siteTitle($site),
            'description' => self::text($site->seo_description()),
            'siteImage' => $image ? ['url' => $image->url(), 'alt' => (string)$image->alt()] : null,
            'path' => $isSite || $model->isHomePage() ? '' : '/' . $model->uri(),
            'origin' => $model->kirby()->option('studio.productionUrl') ?: $model->kirby()->url(),
            'environmentIndexable' => studio_indexable(),
            'publiclyVisible' => $isSite || $model->studioPubliclyVisible(),
            'settingsUrl' => $site->panel()->url(true) . '?tab=settings',
        ];
    }
}
