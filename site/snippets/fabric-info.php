<?php
$sourcePage = $page;

if ($page->intendedTemplate()->name() === 'kuhnya' && $page->parent()) {
    $sourcePage = $page->parent();
}

$fabricInfoTitle = (string)$sourcePage->fabric_info_title()->or('О фабрике')->value();
$fabricInfoText = trim((string)$sourcePage->fabric_info_text()->value());
$fabricLogoFile = $sourcePage->fabric_logo()->toFile();
$fabricLogoAlt = 'Логотип фабрики ' . (string)$sourcePage->title()->value();

$mapLat = $sourcePage->fabric_map_lat()->isNotEmpty() ? (float)$sourcePage->fabric_map_lat()->value() : 55.709744;
$mapLng = $sourcePage->fabric_map_lng()->isNotEmpty() ? (float)$sourcePage->fabric_map_lng()->value() : 37.592538;
$mapZoom = $sourcePage->fabric_map_zoom()->isNotEmpty() ? (int)$sourcePage->fabric_map_zoom()->value() : 13;
$mapLabel = trim((string)$sourcePage->fabric_map_label()->or($sourcePage->title())->value());
$mapId = 'fabric-map-' . preg_replace('/[^a-z0-9]+/i', '-', (string)$sourcePage->id());

if ($fabricInfoText === '') {
    return;
}
?>

<div class="section-wrapper" id="fabric-info">
    <div class="fabric-info__layout">
        <div class="fabric-info__map-wrap">
            <div
                class="fabric-info__map"
                id="<?= esc($mapId, 'attr') ?>"
                data-fabric-map
                data-lat="<?= esc($mapLat, 'attr') ?>"
                data-lng="<?= esc($mapLng, 'attr') ?>"
                data-zoom="<?= esc($mapZoom, 'attr') ?>"
                data-label="<?= esc($mapLabel, 'attr') ?>"
            ></div>
        </div>

        <div class="fabric-info__inner">
            <?php if ($fabricLogoFile): ?>
                <figure class="fabric-info__brand">
                    <div class="fabric-info__logo">
                        <?php snippet('turbo-image', [
                            'image' => $fabricLogoFile,
                            'alt' => $fabricLogoAlt,
                            'width' => 420,
                            'loading' => 'lazy',
                        ]) ?>
                    </div>
                    <figcaption class="fabric-info__eyebrow"><?= esc($sourcePage->title()) ?></figcaption>
                </figure>
            <?php else: ?>
                <a href="/fabrics/<?= esc($sourcePage->title()) ?>>" class="fabric-info__eyebrow"><?= esc($sourcePage->title()) ?></a>
            <?php endif ?>
            <h2><?= esc($fabricInfoTitle) ?></h2>
            <p class="fabric-info__text"><?= esc($fabricInfoText) ?></p>
        </div>
    </div>
</div>
