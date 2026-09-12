<?php
use Kirby\Toolkit\Html;

if (!isset($image) || $image === null || $image === '') {
    return;
}

$explicitAlt = isset($alt);
$alt = $alt ?? '';

$renderWidth = null;
$renderHeight = null;
$src = null;

if (is_string($image)) {
    $candidate = trim($image);
    if ($candidate === '') {
        return;
    }

    if (preg_match('~^(https?:)?//~i', $candidate)) {
        $src = $candidate;
    } else {
        $image = asset(ltrim($candidate, '/'));
    }
}

$width = isset($width) ? (int)$width : 800;
if ($width < 1) {
    $width = 800;
}

$loading = $loading ?? 'lazy';
$class = $class ?? '';
$attrs = (isset($attrs) && is_array($attrs)) ? $attrs : [];
$decoding = $decoding ?? 'async';

if ($src === null) {
    if (!$image instanceof Kirby\Cms\File && !$image instanceof Kirby\Cms\FileVersion && !$image instanceof Kirby\Filesystem\Asset) {
        return;
    }

    if (!$explicitAlt && $image instanceof Kirby\Cms\File) {
        $alt = $image->alt()->or('')->value();
    }

    $extension = method_exists($image, 'extension') ? strtolower((string)$image->extension()) : '';
    $isSvg = $extension === 'svg';

    $rendered = $image;
    if (!$isSvg && method_exists($image, 'resize')) {
        $originalWidth = (int)$image->width();
        if ($originalWidth === 0 || $originalWidth > $width) {
            try {
                $rendered = $image->resize($width);
            } catch (Throwable $e) {
                $rendered = $image;
            }
        }
    }

    $src = relative_url($rendered->url());
    $renderWidth = $rendered->width();
    $renderHeight = $rendered->height();
    if (!$isSvg && method_exists($image, 'srcset')) {
        $sourceWidth = (int)$image->width();
        $candidates = array_values(array_filter([480, 800, 1200, 1600, 2200], fn ($candidate) => $candidate < min($width, $sourceWidth)));
        $candidates[] = min($width, $sourceWidth);
        if ($sourceWidth > 0) {
            $attrs += [
                'srcset' => $image->srcset(array_unique($candidates)),
                'sizes' => $sizes ?? '(max-width: 48rem) 100vw, 80vw',
            ];
        }
    }
}

if (!is_numeric($renderWidth) || (int)$renderWidth <= 0) {
    $renderWidth = null;
}

if (!is_numeric($renderHeight) || (int)$renderHeight <= 0) {
    $renderHeight = null;
}

$imgAttrs = array_merge([
    'src' => $src,
    'class' => $class !== '' ? $class : null,
    'alt' => (string)$alt,
    'loading' => $loading,
    'decoding' => $decoding,
    'width' => $renderWidth,
    'height' => $renderHeight,
], $attrs);

echo '<img' . Html::attr($imgAttrs, null, ' ') . '>';
