<?php
$kuhnya = $kuhnya ?? $page;

$cardTitle = $kuhnya->title()->or('Название кухни')->value();
$fabricPage = $kuhnya->parent();
$fabricsIndex = site()->find('fabrics');
$cardBrand = $fabricPage ? $fabricPage->title()->value() : 'Название фабрики';
$cardBrandUrl = $fabricPage ? $fabricPage->url() : ($fabricsIndex ? $fabricsIndex->url() : '#');
$cardGalleryImages = $kuhnya->kitchen_gallery_images()->toFiles()->filterBy('type', 'image');
if ($cardGalleryImages->isEmpty()) {
    $cardGalleryImages = $kuhnya->images()
        ->filterBy('type', 'image')
        ->filter(function ($image) {
            return strtolower($image->extension()) !== 'svg';
        });
}

$cardImage = $cardGalleryImages->first();
$cardImageAsset = $cardImage ?? asset('assets/placeholder.svg');
$cardIntro = $kuhnya->intro()->or('Сдержанная кухня с продуманной эргономикой, аккуратными линиями и практичными материалами для комфортной жизни.')->value();
$cardCountry = $kuhnya->country_of_origin()->or('Италия')->value();
$cardPrice = $kuhnya->price()->or('от 0,000,000 рублей')->value();
$cardSpecs = $kuhnya->kitchen_specs()->toStructure();
$layout = $layout ?? 'default';

if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    if (mb_strlen($cardIntro) > 180) {
        $cardIntro = rtrim(mb_substr($cardIntro, 0, 180)) . '...';
    }
} elseif (strlen($cardIntro) > 180) {
    $cardIntro = rtrim(substr($cardIntro, 0, 180)) . '...';
}
?>

<article class="kuhnya-card-overview<?= $layout === 'content-only' ? ' kuhnya-card-overview--content-only' : '' ?>">
    <div class="kuhnya-card-overview__content">
            <a class="internal-link__hidden kuhnya-card-overview__eyebrow" href="<?= esc($cardBrandUrl, 'attr') ?>"><?= esc($cardBrand) ?></a>
            <a class="internal-link__hidden kuhnya-card-overview__title"><?= esc($cardTitle) ?></a>
            <p class="kuhnya-card-overview__intro"><?= esc($cardIntro) ?></p>

            <ul class="kuhnya-card-overview__facts">
                <li class="kuhnya-card-overview__fact-item">
                    <span class="kuhnya-card-overview__fact-value"><?= esc($cardCountry) ?></span>
                </li>
                <li class="kuhnya-card-overview__fact-item">
                    <span class="kuhnya-card-overview__fact-value"><?= esc($cardPrice) ?></span>
                </li>
                <?php foreach ($cardSpecs as $spec): ?>
                    <?php
                    $specLabel = trim($spec->label()->value());
                    $specValue = trim($spec->value()->value());
                    if ($specLabel === '' && $specValue === '') {
                        continue;
                    }
                    ?>
                    <li class="kuhnya-card-overview__fact-item">
                        <span class="kuhnya-card-overview__fact-label"><?= esc($specLabel !== '' ? $specLabel : 'detail') ?></span>
                        <span class="kuhnya-card-overview__fact-value"><?= esc($specValue) ?></span>
                    </li>
                <?php endforeach ?>
            </ul>

            <?php if (($showLink ?? false) === true): ?>
                <a class="internal-link" href="<?= $kuhnya->url() ?>">подробности</a>
            <?php endif ?>
    </div>

    <?php if ($layout !== 'content-only'): ?>
        <a class="kuhnya-card-overview__media" href="<?= $kuhnya->url() ?>" aria-label="<?= esc($cardTitle, 'attr') ?>">
            <?php snippet('turbo-image', [
                'image' => $cardImageAsset,
                'alt' => $cardTitle,
                'width' => 960,
            ]) ?>
        </a>
    <?php endif ?>
</article>