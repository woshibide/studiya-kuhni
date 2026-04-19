<?php
$galleryHeading = 'Привезти вам новую кухню?';
$galleryImages = [];
$isKitchenPage = $page->intendedTemplate()->name() === 'kuhnya';

if ($isKitchenPage) {
    $galleryImages = $page->kitchen_gallery_images()->toFiles()->filterBy('type', 'image');

    if ($galleryImages->isEmpty()) {
        $galleryImages = $page->images()
            ->filterBy('type', 'image')
            ->filter(function ($image) {
                return strtolower($image->extension()) !== 'svg';
            });
    }
} elseif ($page->gallery()->isNotEmpty()) {
    $galleryData = $page->gallery()->yaml();
    if (is_array($galleryData)) {
        $galleryHeading = $galleryData['gallery_heading'] ?? $galleryHeading;

        $imageIds = $galleryData['gallery_images'] ?? [];
        if (is_string($imageIds)) {
            $imageIds = preg_split('/\R+/', trim($imageIds)) ?: [];
        }

        if (is_array($imageIds)) {
            foreach ($imageIds as $id) {
                $id = trim((string)$id);
                if ($id === '') {
                    continue;
                }

                if ($file = $page->file($id)) {
                    $galleryImages[] = $file;
                }
            }
        }
    }
} else {
    $galleryHeading = $page->gallery_heading()->or($galleryHeading)->value();
    $galleryImages = $page->gallery_images()->toFiles()->values();
}

$galleryImages = array_values(iterator_to_array($galleryImages));

if (empty($galleryImages)) {
    return;
}

$kuhnyaTitle = trim((string)$page->title()->value());
$fabricPage = $page->parent();
$fabricsIndex = site()->find('fabrics');
$kuhnyaBrand = $fabricPage ? $fabricPage->title()->value() : 'Название фабрики';
$kuhnyaBrandUrl = relative_url($fabricPage ? $fabricPage->url() : ($fabricsIndex ? $fabricsIndex->url() : '#'));
$kuhnyaCountry = trim((string)$page->country_of_origin()->value());
$kuhnyaPrice = trim((string)$page->price()->value());
$kuhnyaIntro = trim((string)$page->intro()->value());
$kuhnyaSpecs = $page->kitchen_specs()->toStructure();

if ($isKitchenPage) {
    $kuhnyaBlueprint = $page->blueprint();
    $kuhnyaFieldDefault = function (string $fieldName) use ($kuhnyaBlueprint): string {
        $field = $kuhnyaBlueprint->field($fieldName);
        return trim((string)($field['default'] ?? ''));
    };

    if ($kuhnyaIntro === '') {
        $kuhnyaIntro = $kuhnyaFieldDefault('intro');
    }

    if ($kuhnyaCountry === '') {
        $kuhnyaCountry = $kuhnyaFieldDefault('country_of_origin');
    }

    if ($kuhnyaPrice === '') {
        $kuhnyaPrice = $kuhnyaFieldDefault('price');
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($kuhnyaIntro) > 180) {
            $kuhnyaIntro = rtrim(mb_substr($kuhnyaIntro, 0, 180)) . '...';
        }
    } elseif (strlen($kuhnyaIntro) > 180) {
        $kuhnyaIntro = rtrim(substr($kuhnyaIntro, 0, 180)) . '...';
    }
}
?>

<div class="section-wrapper" id="gallery">
    <h2><?= esc($galleryHeading) ?></h2>
    <p class="gallery__intro">Lorem ipsum dolor sit amet consectetur adipiscing elit quisque faucibus ex sapien vitae pellentesque sem placerat in id cursus mi pretium tellus duis convallis tempus leo eu aenean sed diam.</p>

    <div class="gallery" data-gallery data-gallery-count="<?= (int)count($galleryImages) ?>">
        <figure class="gallery-inline">
            <ul class="gallery-inline__list" data-gallery-list>
                <?php foreach ($galleryImages as $index => $image): ?>
                    <li class="gallery-inline__item">
                        <button
                            type="button"
                            class="gallery-inline__trigger"
                            data-gallery-open
                            data-index="<?= (int)$index ?>"
                            aria-label="Открыть изображение <?= (int)$index + 1 ?>"
                        >
                            <?php snippet('turbo-image', [
                                'image' => $image,
                                'alt' => $image->alt()->or($kuhnyaTitle)->value(),
                                'width' => 960,
                                'loading' => 'lazy',
                            ]) ?>
                        </button>
                    </li>
                <?php endforeach ?>
            </ul>
        </figure>

        <div class="gallery-overlay" data-gallery-overlay aria-hidden="true" hidden>
            <div class="gallery-overlay__backdrop" data-gallery-close></div>
            <div class="gallery-overlay__layout">
                <button class="gallery-overlay__nav gallery-overlay__nav--prev" type="button" data-gallery-prev aria-label="Предыдущее изображение">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M14.7 5.3a1 1 0 0 1 0 1.4L9.41 12l5.3 5.3a1 1 0 1 1-1.42 1.4l-6-6a1 1 0 0 1 0-1.4l6-6a1 1 0 0 1 1.4 0Z" fill="currentColor" />
                    </svg>
                </button>

                <div class="gallery-overlay__main embla" data-gallery-overlay-embla>
                    <div class="gallery-overlay__viewport" data-gallery-overlay-viewport>
                        <div class="gallery-overlay__container">
                            <?php foreach ($galleryImages as $image): ?>
                                <div class="gallery-overlay__slide">
                                    <div class="gallery-overlay__image-frame">
                                        <?php snippet('turbo-image', [
                                            'image' => $image,
                                            'alt' => $image->alt()->or($kuhnyaTitle)->value(),
                                            'width' => 1800,
                                            'loading' => 'lazy',
                                        ]) ?>
                                        <button class="gallery-overlay__close" type="button" data-gallery-close aria-label="Закрыть галерею">
                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M6.7 5.3a1 1 0 0 1 1.4 0L12 9.17l3.9-3.87a1 1 0 0 1 1.4 1.4L13.42 10.6l3.88 3.9a1 1 0 1 1-1.4 1.4L12 12.02l-3.9 3.88a1 1 0 0 1-1.4-1.4l3.88-3.9L6.7 6.7a1 1 0 0 1 0-1.4Z" fill="currentColor" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>

                    <?php if ($isKitchenPage): ?>
                        <div class="gallery-overlay__meta">
                            <article class="gallery-overlay__meta-card" aria-label="<?= esc($kuhnyaTitle, 'attr') ?>">
                                <a class="gallery-overlay__eyebrow" href="<?= esc($kuhnyaBrandUrl, 'attr') ?>"><?= esc($kuhnyaBrand) ?></a>
                                <h3 class="gallery-overlay__titleline"><?= esc($kuhnyaTitle) ?></h3>

                                <?php if ($kuhnyaIntro !== ''): ?>
                                    <p class="gallery-overlay__intro"><?= esc($kuhnyaIntro) ?></p>
                                <?php endif ?>

                                <ul class="gallery-overlay__facts">
                                    <?php if ($kuhnyaCountry !== ''): ?>
                                        <li class="gallery-overlay__fact-item">
                                            <span class="gallery-overlay__fact-value"><?= esc($kuhnyaCountry) ?></span>
                                        </li>
                                    <?php endif ?>

                                    <?php if ($kuhnyaPrice !== ''): ?>
                                        <li class="gallery-overlay__fact-item gallery-overlay__fact-item--price">
                                            <span class="gallery-overlay__fact-value"><?= esc($kuhnyaPrice) ?></span>
                                            <button class="gallery-overlay__cta" type="button" data-open-nav-contact>
                                                узнать подробности
                                            </button>
                                        </li>
                                    <?php endif ?>

                                    <?php foreach ($kuhnyaSpecs as $spec): ?>
                                        <?php
                                        $specLabel = trim($spec->label()->value());
                                        $specValue = trim($spec->value()->value());
                                        if ($specLabel === '' && $specValue === '') {
                                            continue;
                                        }
                                        ?>
                                        <li class="gallery-overlay__fact-item">
                                            <span class="gallery-overlay__fact-label"><?= esc($specLabel !== '' ? $specLabel : 'detail') ?></span>
                                            <span class="gallery-overlay__fact-value"><?= esc($specValue) ?></span>
                                        </li>
                                    <?php endforeach ?>
                                </ul>
                            </article>
                        </div>
                    <?php endif ?>
                </div>

                <button class="gallery-overlay__nav gallery-overlay__nav--next" type="button" data-gallery-next aria-label="Следующее изображение">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M9.3 18.7a1 1 0 0 1 0-1.4l5.29-5.3-5.3-5.3a1 1 0 0 1 1.42-1.4l6 6a1 1 0 0 1 0 1.4l-6 6a1 1 0 0 1-1.4 0Z" fill="currentColor" />
                    </svg>
                </button>

            </div>
        </div>
    </div>
</div>
