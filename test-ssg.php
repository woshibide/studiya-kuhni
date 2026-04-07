<?php
require 'kirby/bootstrap.php';
$kirby = new Kirby();
try {
    $list = \JR\StaticSiteGenerator::generateFromConfig($kirby);
    echo "✓ done\n";
    echo "📦 files: " . count($list) . "\n";
} catch (Throwable $e) {
    echo "✗ " . $e->getMessage() . "\n";
}
