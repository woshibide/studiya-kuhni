<?php

namespace Studio\Seo;

use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Http\Response;

/** Keeps the upstream renderer's output out of editorial content. */
final class OgRenderPage extends Page
{
    public string $renderedImage = '';

    public function title(): \Kirby\Content\Field
    {
        // Suppress Kirby's slug fallback; typography is composed by the adapter.
        return new \Kirby\Content\Field($this, 'title', '');
    }

    public function createFile(array $props, bool $move = false): File
    {
        $this->renderedImage = file_get_contents($props['source']);
        return new File(['filename' => $props['filename'], 'parent' => $this]);
    }
}

final class OgImage
{
    private const VERSION = '3';

    private static function signature(): string
    {
        static $signature;
        return $signature ??= hash('sha256', self::VERSION . hash_file('sha256', self::options()['image.template']) . hash_file('sha256', self::options()['font.path']));
    }

    public static function options(): array
    {
        $assets = dirname(__DIR__, 3) . '/assets/og';
        return [
            'width' => 1200, 'height' => 630, 'field' => 'seo_image',
            'font.path' => $assets . "/Suisse Int'l Medium.ttf",
            'font.size' => 84, 'font.lineheight' => 1.31, 'font.color' => [0, 0, 0],
            'image.template' => $assets . '/template.png',
            'title.field' => 'seo_title', 'title.position' => [40, 72],
            // Pixel-based Unicode wrapping is done before calling the plugin.
            'title.charactersPerLine' => 10000,
            'heroImage.field' => 'studio_og_unused',
            'heroImage.fallbackColor' => [255, 255, 255],
        ];
    }

    public static function routes(): array
    {
        return [
            ['pattern' => 'og-image', 'action' => fn () => OgImage::response('og-image')],
            ['pattern' => '(:all)/og-image', 'action' => fn ($path) => OgImage::response($path . '/og-image')],
        ];
    }

    public static function settings(Page $page): array
    {
        return self::normalize([
            'size' => $page->seo_og_size()->value(),
            'x' => $page->seo_og_x()->value(),
            'y' => $page->seo_og_y()->value(),
        ]);
    }

    public static function normalize(array $settings): array
    {
        $number = static fn ($value, $default, $min, $max) => is_numeric($value)
            ? (int)round(max($min, min($max, (float)$value))) : $default;
        return [
            'size' => $number($settings['size'] ?? null, 112, 48, 144),
            'x' => $number($settings['x'] ?? null, 40, 24, 1152),
            'y' => $number($settings['y'] ?? null, 72, 24, 296),
        ];
    }

    public static function revision(Page $page): string
    {
        return substr(hash('sha256', self::signature() . json_encode([Metadata::coverTitle($page), self::settings($page)])), 0, 16);
    }

    /** Preserve editorial line breaks, including for Cyrillic and long unbroken words. */
    public static function wrap(string $text, float $size = 48, int $width = 1056, int $maxLines = 4): string
    {
        $font = self::options()['font.path'];
        $measure = static function (string $value) use ($size, $font): int {
            $box = imagettfbbox($size, 0, $font, $value);
            return max($box[0], $box[2], $box[4], $box[6]) - min($box[0], $box[2], $box[4], $box[6]);
        };
        $lines = [];
        foreach (explode("\n", self::text($text)) as $paragraph) {
            $line = '';
            foreach (preg_split('//u', $paragraph, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                if ($line !== '' && $measure($line . $char) > $width) {
                    $break = mb_strrpos($line, ' ');
                    if ($break !== false && $break > 0) {
                        $lines[] = mb_substr($line, 0, $break);
                        $line = ltrim(mb_substr($line, $break + 1)) . $char;
                    } else {
                        $lines[] = rtrim($line);
                        $line = ltrim($char);
                    }
                } else {
                    $line .= $char;
                }
            }
            $lines[] = rtrim($line);
        }
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $last = array_pop($lines);
            while ($measure($last . '…') > $width && $last !== '') $last = mb_substr($last, 0, -1);
            $lines[] = rtrim($last) . '…';
        }
        return implode("\n", $lines);
    }

    public static function text(string $text): string
    {
        return trim(preg_replace('/[^\S\n]+/u', ' ', str_replace(["\r\n", "\r"], "\n", mb_substr($text, 0, 500))) ?? '');
    }

    /** All coordinates use output pixels. The logo begins below the safe title area. */
    public static function layout(string $title, array $settings = []): array
    {
        $settings = self::normalize($settings);
        $font = self::options()['font.path'];
        $size = $settings['size'];
        do {
            $points = $size * .75; // GD uses points at 96 DPI.
            $lines = explode("\n", self::wrap($title, $points, 1120, 500));
            $step = (int)round($size * .98);
            $boxes = array_map(static fn ($line) => imagettfbbox($points, 0, $font, $line), $lines);
            $top = min(array_column($boxes, 7));
            $bottom = max(array_column($boxes, 1));
            $height = $bottom - $top + (count($lines) - 1) * $step;
            if ($height <= 272 || $size <= 48) break;
            $size--;
        } while (true);
        $truncated = $height > 272;
        if ($truncated) {
            $maxLines = max(1, (int)floor((272 - ($bottom - $top)) / $step) + 1);
            $lines = explode("\n", self::wrap($title, $points, 1120, $maxLines));
            $boxes = array_map(static fn ($line) => imagettfbbox($points, 0, $font, $line), $lines);
            $top = min(array_column($boxes, 7));
            $bottom = max(array_column($boxes, 1));
            $height = $bottom - $top + (count($lines) - 1) * $step;
        }
        $width = max(array_map(static fn ($box) => $box[2] - $box[0], $boxes));
        $x = min($settings['x'], 1176 - $width);
        $y = min($settings['y'], 320 - $height);
        return [
            'lines' => $lines, 'size' => $size, 'requestedSize' => $settings['size'],
            'x' => $x, 'y' => $y, 'width' => $width, 'height' => $height,
            'baseline' => $y - $top, 'step' => $step, 'truncated' => $truncated,
        ];
    }

    public static function render(string $title, string $siteTitle = '', array $settings = []): string
    {
        $kirby = App::instance();
        $layout = self::layout($title, $settings);
        // Let the installed plugin compose the template; draw adjustable typography here.
        $page = new OgRenderPage([
            'slug' => 'og-render',
            'root' => sys_get_temp_dir() . '/studio-og-virtual',
            'files' => [], 'content' => ['title' => '', 'seo_title' => ''],
        ]);
        $kirby->impersonate('kirby', static fn () => $page->createOgImage('default'));
        $canvas = imagecreatefromstring($page->renderedImage);
        $color = imagecolorallocate($canvas, 0, 0, 0);
        $font = self::options()['font.path'];
        foreach ($layout['lines'] as $index => $line) {
            $bounds = imagettfbbox($layout['size'] * .75, 0, $font, $line);
            imagettftext($canvas, $layout['size'] * .75, 0, $layout['x'] - $bounds[0], $layout['baseline'] + $index * $layout['step'], $color, $font, $line);
        }
        ob_start();
        imagepng($canvas);
        return ob_get_clean();
    }

    public static function response(string $path): Response
    {
        $kirby = App::instance();
        $page = $path === 'og-image' ? $kirby->site()->homePage() : $kirby->page(substr($path, 0, -9));
        if (!$page || !$page->studioPubliclyVisible()) return new Response('', 'text/plain', 404);
        $image = Metadata::image($page);
        if (!$image['generated']) return Response::redirect($image['url']);

        $cache = $kirby->cache('studio.seo.og');
        $key = hash('sha256', $page->id());
        $revision = self::revision($page);
        $cached = $cache->get($key);
        if (($cached['revision'] ?? null) === $revision) {
            $png = base64_decode($cached['png']);
        } else {
            $png = self::render(Metadata::coverTitle($page), '', self::settings($page));
            // One cache entry per page; unsaved Panel previews never fill disk cache.
            $cache->set($key, ['revision' => $revision, 'png' => base64_encode($png)], 60 * 24 * 7);
        }
        return new Response($png, 'image/png', 200, [
            'Cache-Control' => 'public, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
