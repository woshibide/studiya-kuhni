<?php
$contextPage = $contextPage ?? $page;
$currentFabric = $currentFabric ?? null;

if (!$currentFabric && $contextPage) {
    $templateName = $contextPage->intendedTemplate()->name();

    if ($templateName === 'fabric') {
        $currentFabric = $contextPage;
    } elseif ($templateName === 'kuhnya') {
        $currentFabric = $contextPage->parent();
    }
}

$fabricsPage = page('fabrics');

if (!$fabricsPage) {
    return;
}

$otherFabrics = $fabricsPage->childrenAndDrafts();
$placeholderImageUrl = relative_url('assets/placeholder.svg');

$resolveKitchenGalleryImages = static function ($kitchen) {
    $selected = $kitchen->kitchen_gallery_images()->toFiles()->filterBy('type', 'image');
    if ($selected->isNotEmpty()) {
        return $selected;
    }

    return $kitchen->images()
        ->filterBy('type', 'image')
        ->filter(function ($image) {
            return strtolower($image->extension()) !== 'svg';
        });
};

$resolveOptimizedImageUrl = static function ($image, int $width = 1600) use ($placeholderImageUrl): string {
    if (!$image || !is_object($image) || !method_exists($image, 'url')) {
        return $placeholderImageUrl;
    }

    $extension = method_exists($image, 'extension') ? strtolower((string)$image->extension()) : '';
    if ($extension === 'svg' || !method_exists($image, 'resize')) {
        return $image->url();
    }

    $sourceWidth = method_exists($image, 'width') ? (int)$image->width() : 0;
    if ($sourceWidth > 0 && $sourceWidth <= $width) {
        return $image->url();
    }

    try {
        return $image->resize($width)->url();
    } catch (Throwable $e) {
        return $image->url();
    }
};

if ($currentFabric) {
    $otherFabrics = $otherFabrics->filter(fn ($fabric) => $fabric->id() !== $currentFabric->id());
}

if ($otherFabrics->isEmpty()) {
    return;
}
?>
<section class="other-fabrics-section">
    <h2>Другие производители</h2>

    <div class="fabric-grid">
        <?php foreach ($otherFabrics as $fabric): ?>
            <?php
            $kitchens = $fabric->childrenAndDrafts();
            $kitchenLinks = [];
            $kitchenSlides = [];

            if ($kitchens->count() === 1) {
                $kuhnya = $kitchens->first();
                if ($kuhnya) {
                    $kitchenImages = $resolveKitchenGalleryImages($kuhnya);
                    $primaryImage = $kitchenImages->first();
                    $kitchenLinks[] = [
                        'title' => (string)$kuhnya->title(),
                        'url' => relative_url($kuhnya->url()),
                        'image' => $resolveOptimizedImageUrl($primaryImage, 1200),
                        'slideIndex' => 0,
                    ];

                    $galleryImages = $kitchenImages->limit(5);
                    if ($galleryImages->isNotEmpty()) {
                        foreach ($galleryImages as $image) {
                            $kitchenSlides[] = [
                                'image' => $resolveOptimizedImageUrl($image, 1600),
                            ];
                        }
                    }
                }
            } else {
                foreach ($kitchens as $index => $kuhnya) {
                    $kitchenImage = $resolveKitchenGalleryImages($kuhnya)->first();
                    $kitchenImageUrl = $resolveOptimizedImageUrl($kitchenImage, 1200);

                    $kitchenLinks[] = [
                        'title' => (string)$kuhnya->title(),
                        'url' => relative_url($kuhnya->url()),
                        'image' => $kitchenImageUrl,
                        'slideIndex' => $index,
                    ];

                    $kitchenSlides[] = [
                        'image' => $kitchenImageUrl,
                    ];
                }
            }

            if (empty($kitchenSlides)) {
                $kitchenSlides[] = [
                    'image' => $placeholderImageUrl,
                ];
            }

            $cardImageUrl = $kitchenSlides[0]['image'];
            ?>
            <figure
                class="fabric-card"
                data-fabric-card
                data-default-image="<?= esc($cardImageUrl, 'attr') ?>"
            >
                <div class="fabric-card__media" data-other-embla>
                    <div class="fabric-card__media-viewport" data-other-embla-viewport>
                        <div class="fabric-card__media-container">
                            <?php if (!empty($kitchenSlides)): ?>
                                <?php foreach ($kitchenSlides as $slide): ?>
                                    <div
                                        class="fabric-card__media-slide"
                                        style="background-image: url('<?= esc($slide['image'], 'attr') ?>');"
                                        aria-hidden="true"
                                    ></div>
                                <?php endforeach ?>
                            <?php else: ?>
                                <div
                                    class="fabric-card__media-slide"
                                    style="background-image: url('<?= esc($cardImageUrl, 'attr') ?>');"
                                    aria-hidden="true"
                                ></div>
                            <?php endif ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($kitchenLinks)): ?>
                    <?php $kitchenCount = count($kitchenLinks); ?>
                    <?php $kitchenIndex = 1; ?>
                    <ul class="fabric-card__kitchens">
                        <?php foreach ($kitchenLinks as $link): ?>
                            <li style="--kitchen-index: <?= $kitchenIndex ?>; --kitchen-count: <?= $kitchenCount ?>;">
                                <a
                                    class="internal-link"
                                    href="<?= esc($link['url']) ?>"
                                    data-fabric-image="<?= esc($link['image'], 'attr') ?>"
                                    data-other-slide-index="<?= $link['slideIndex'] ?>"
                                >
                                    <?= esc($link['title']) ?>
                                </a>
                            </li>
                            <?php $kitchenIndex++; ?>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>

                <figcaption class="fabric-card__caption">
                    <a href="<?= esc(relative_url($fabric->url()), 'attr') ?>"><?= esc($fabric->title()) ?></a>
                </figcaption>
            </figure>
        <?php endforeach ?>
    </div>
</section>
