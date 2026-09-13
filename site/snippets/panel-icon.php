<?php

// Admin Bar 2.3.0 calls an undefined icon() helper for aliases in Kirby's sprite.
$name ??= null;
$requestedName = $name;
$panelIcons = svg($kirby->root('kirby') . '/panel/dist/img/icons.svg');
$seen = [];
while ($panelIcons && is_string($name) && !isset($seen[$name])) {
    $seen[$name] = true;
    if (!preg_match('/<symbol[^>]*id="icon-' . preg_quote($name, '/') . '"[^>]*viewBox="([^"]*)"[^>]*>(.*?)<\/symbol>/s', $panelIcons, $matches)) return;
    if (preg_match('/<use href="#icon-([^"]+)"[^>]*>/s', $matches[2], $alias)) {
        $name = $alias[1];
        continue;
    }
    echo '<svg class="k-icon" aria-hidden="true" data-type="' . esc($requestedName, 'attr') . '" xmlns="http://www.w3.org/2000/svg" viewBox="' . esc($matches[1], 'attr') . '">' . $matches[2] . '</svg>';
    break;
}
