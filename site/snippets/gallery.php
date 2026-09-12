<?php
$galleryHeading = 'Привезти вам новую кухню?';
$galleryImages = [];
$isKitchenPage = $page->intendedTemplate()->name() === 'kuhnya';

if ($isKitchenPage) {
    $galleryImages = $page->studioKitchenImages();
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
$kuhnyaIntro = $page->intro()->studioPlainText(180);
$kuhnyaSpecs = $page->kitchen_specs()->toStructure();

if ($isKitchenPage) {
    $kuhnyaBlueprint = $page->blueprint();
    $kuhnyaFieldDefault = function (string $fieldName) use ($kuhnyaBlueprint): string {
        $field = $kuhnyaBlueprint->field($fieldName);
        return trim((string)($field['default'] ?? ''));
    };

    if ($kuhnyaIntro === '') {
        $kuhnyaIntro = (clone $page->intro())->value($kuhnyaFieldDefault('intro'))->studioPlainText(180);
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
    <p class="gallery__intro">Рассмотрите кухню со всех сторон. Откройте галерею фотографий, чтобы изучить детали и принять решение.</p>

    <div class="gallery" data-gallery data-gallery-count="<?= (int)count($galleryImages) ?>">
        <div class="gallery-inline__navigation" data-gallery-scroll-controls role="group" aria-label="Прокрутка фотографий" hidden>
            <button type="button" class="gallery-inline__arrow" data-gallery-scroll-prev aria-label="Прокрутить фотографии влево" disabled>
                <svg width="32" height="24" viewBox="0 0 32 24" fill="none" aria-hidden="true"><path d="M29 12H3m9-9-9 9 9 9" stroke="currentColor" stroke-width="1.5" /></svg>
            </button>
            <button type="button" class="gallery-inline__arrow" data-gallery-scroll-next aria-label="Прокрутить фотографии вправо" disabled>
                <svg width="32" height="24" viewBox="0 0 32 24" fill="none" aria-hidden="true"><path d="M3 12h26m-9-9 9 9-9 9" stroke="currentColor" stroke-width="1.5" /></svg>
            </button>
        </div>
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

        <dialog class="gallery-overlay" data-gallery-overlay aria-label="Галерея: <?= esc($kuhnyaTitle, 'attr') ?>" hidden>
            <div class="gallery-overlay__backdrop" data-gallery-close></div>
            <div class="gallery-overlay__image-frame" data-gallery-frame aria-busy="false">
                <button class="gallery-overlay__photo" type="button" data-gallery-expand aria-label="Развернуть фотографию" aria-pressed="false">
                    <img class="gallery-overlay__image" data-gallery-image alt="" decoding="async" width="<?= (int)$galleryImages[0]->width() ?>" height="<?= (int)$galleryImages[0]->height() ?>">
                    <img class="gallery-overlay__image" data-gallery-image-buffer alt="" aria-hidden="true" decoding="async" width="<?= (int)$galleryImages[0]->width() ?>" height="<?= (int)$galleryImages[0]->height() ?>">
                </button>
                <p class="gallery-overlay__error" data-gallery-error role="alert" hidden>
                    Не удалось загрузить фото.
                    <button type="button" data-gallery-retry>Попробовать снова</button>
                </p>
            </div>
            <div class="gallery-overlay__layout">
                <button class="gallery-overlay__close" type="button" data-gallery-close aria-label="Закрыть галерею">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
                </button>
                <button class="gallery-overlay__nav gallery-overlay__nav--prev" type="button" data-gallery-prev aria-label="Предыдущее изображение">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6" /></svg>
                </button>
                <button class="gallery-overlay__nav gallery-overlay__nav--next" type="button" data-gallery-next aria-label="Следующее изображение">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m10 6 6 6-6 6" /></svg>
                </button>
                <div class="gallery-overlay__main">
                    <div class="gallery-overlay__image-slot" data-gallery-slot aria-hidden="true"></div>
                    <div class="gallery-overlay__footer">
                        <div class="gallery-overlay__explorer">
                            <div class="gallery-overlay__thumbnails" aria-label="Фотографии кухни">
                                <?php foreach ($galleryImages as $index => $image): ?>
                                    <button
                                        class="gallery-overlay__thumbnail"
                                        type="button"
                                        data-gallery-thumbnail
                                        data-index="<?= (int)$index ?>"
                                        data-gallery-key="<?= esc($image->filename(), 'attr') ?>"
                                        data-gallery-src="<?= esc(relative_url($image->resize(2200)->url()), 'attr') ?>"
                                        aria-label="Показать изображение <?= (int)$index + 1 ?>"
                                        aria-pressed="false"
                                    >
                                        <span class="gallery-overlay__thumbnail-map">
                                            <?php snippet('turbo-image', [
                                                'image' => $image,
                                                'alt' => $image->alt()->or($kuhnyaTitle)->value(),
                                                'width' => 320,
                                                'sizes' => '160px',
                                                'loading' => 'lazy',
                                                'attrs' => ['draggable' => 'false'],
                                            ]) ?>
                                            <span class="gallery-overlay__crop" aria-hidden="true"></span>
                                        </span>
                                    </button>
                                <?php endforeach ?>
                            </div>
                        </div>
                        <?php if ($isKitchenPage): ?>
                            <div class="gallery-overlay__meta">
                                <button class="gallery-overlay__share" type="button" data-gallery-share aria-label="Скопировать ссылку на фотографию" title="Скопировать ссылку">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="12" height="13" rx="2" /><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h3" /></svg>
                                </button>
                                <span class="gallery-overlay__share-status" data-gallery-share-status role="status" aria-live="polite"></span>
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
                                    <input class="gallery-overlay__share-link" data-gallery-share-link aria-label="Ссылка на фотографию: скопируйте её" readonly hidden>
                                </article>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </dialog>
    </div>
</div>
