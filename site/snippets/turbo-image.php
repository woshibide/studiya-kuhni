<?php
use Kirby\Toolkit\Html;

if (!isset($image) || $image === null || $image === '') {
    return;
}

if (!isset($alt) || $alt === null) {
    $alt = '';
}

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
    if (!is_object($image) || !method_exists($image, 'url')) {
        return;
    }

    if ($alt === '' && method_exists($image, 'alt')) {
        $alt = $image->alt()->or('')->value();
    }

    $extension = method_exists($image, 'extension') ? strtolower((string)$image->extension()) : '';
    $isSvg = $extension === 'svg';

    $rendered = $image;
    if (!$isSvg && method_exists($image, 'resize')) {
        $originalWidth = method_exists($image, 'width') ? (int)$image->width() : 0;
        if ($originalWidth === 0 || $originalWidth > $width) {
            try {
                $rendered = $image->resize($width);
            } catch (Throwable $e) {
                $rendered = $image;
            }
        }
    }

    $src = $rendered->url();
    $renderWidth = method_exists($rendered, 'width') ? $rendered->width() : null;
    $renderHeight = method_exists($rendered, 'height') ? $rendered->height() : null;
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
