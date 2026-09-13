<?php

// Converts the supplied artwork to the PNG format required by OG Image.
$root = dirname(__DIR__);
$source = $root . '/assets/og/og-image_OG combined.jpg';
$canvas = imagecreatefromjpeg($source);
if (!$canvas || imagesx($canvas) !== 1200 || imagesy($canvas) !== 630) {
    throw new RuntimeException('OG artwork must be 1200 × 630 pixels.');
}
imagepng($canvas, $root . '/assets/og/template.png');
echo "OG template generated from supplied artwork: 1200 × 630\n";
