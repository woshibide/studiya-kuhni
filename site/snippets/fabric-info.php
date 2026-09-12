<?php
$sourcePage = $page;

if ($page->intendedTemplate()->name() === 'kuhnya' && $page->parent()) {
    $sourcePage = $page->parent();
}

$fabricInfoTitle = (string)$sourcePage->fabric_info_title()->or('О фабрике')->value();
$fabricInfoText = $sourcePage->fabric_info_text()->studioText(false, 3);
$fabricLogoFile = $sourcePage->fabric_logo()->toFile();
$fabricLogoAlt = 'Логотип мебельной фабрики ' . (string)$sourcePage->title()->value();

$mapLocation = $sourcePage->studioMapLocation();
$mapId = 'fabric-map-' . preg_replace('/[^a-z0-9]+/i', '-', (string)$sourcePage->id());
$mapStatusId = $mapId . '-status';
$mapTileUrl = (string)option('studio.map.tileUrl', 'https://tiles.maps.eox.at/wmts/1.0.0/s2cloudless_3857/default/g/{z}/{y}/{x}.jpg');
$mapAttribution = (string)option('studio.map.attribution', '<a href="https://cloudless.eox.at/">EOxCloudless</a> by <a href="https://eox.at/">EOX IT Services GmbH</a> (Contains modified Copernicus Sentinel data 2016) · <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a> · <a href="https://maps.eox.at/">EOX::Maps</a>');

$mapMaxZoom = max(0, min(19, (int)option('studio.map.maxZoom', 14)));

if ($fabricInfoText === '' && !$mapLocation && !$fabricLogoFile) {
    return;
}
?>

<div class="section-wrapper" id="fabric-info">
    <div class="fabric-info__layout">
        <div class="fabric-info__map-wrap">
            <?php if ($mapLocation): ?>
                <div
                    class="fabric-info__map"
                    id="<?= esc($mapId, 'attr') ?>"
                    role="region"
                    aria-label="<?= esc('Расположение фабрики ' . $mapLocation['label'], 'attr') ?>"
                    aria-describedby="<?= esc($mapStatusId, 'attr') ?>"
                    data-fabric-map
                    data-lat="<?= esc($mapLocation['lat'], 'attr') ?>"
                    data-lng="<?= esc($mapLocation['lng'], 'attr') ?>"
                    data-max-zoom="<?= $mapMaxZoom ?>"
                    data-zoom="<?= esc($mapLocation['zoom'], 'attr') ?>"
                    data-label="<?= esc($mapLocation['label'], 'attr') ?>"
                    data-tile-url="<?= esc($mapTileUrl, 'attr') ?>"
                    data-attribution="<?= esc($mapAttribution, 'attr') ?>"
                ></div>
                <p class="fabric-info__map-status" id="<?= esc($mapStatusId, 'attr') ?>" role="status" aria-live="polite">
                    <span data-fabric-map-status></span>
                    Расположение фабрики отмечено <a class="fabric-info__link hover-underline" href="<?= esc('https://www.openstreetmap.org/?mlat=' . $mapLocation['lat'] . '&mlon=' . $mapLocation['lng'] . '#map=' . $mapLocation['zoom'] . '/' . $mapLocation['lat'] . '/' . $mapLocation['lng'], 'attr') ?>" target="_blank" rel="noopener noreferrer">на карте</a>.
                </p>
            <?php else: ?>
                <div class="fabric-info__map-empty">
                    <p>Расположение фабрики пока не указано.</p>
                </div>
            <?php endif ?>
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
                <a href="<?= esc(relative_url($sourcePage->url()), 'attr') ?>" class="fabric-info__eyebrow"><?= esc($sourcePage->title()) ?></a>
            <?php endif ?>
            <h2><?= esc($fabricInfoTitle) ?></h2>
            <?php if ($fabricInfoText !== ''): ?>
                <div class="fabric-info__text"><?= $fabricInfoText ?></div>
            <?php endif ?>
        </div>
    </div>
</div>
