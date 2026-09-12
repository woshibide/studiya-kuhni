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

$otherFabrics = $fabricsPage->children()->filter(fn ($entry) => $entry->studioPubliclyVisible());
$placeholderImageUrl = relative_url('assets/placeholder.svg');

$resolveKitchenGalleryImages = static fn ($kitchen) => $kitchen->studioKitchenImages();

$resolveOptimizedImageUrl = static function ($image, int $width = 1600) use ($placeholderImageUrl): string {
    if (!$image || !is_object($image) || !method_exists($image, 'url')) {
        return $placeholderImageUrl;
    }

    $extension = method_exists($image, 'extension') ? strtolower((string)$image->extension()) : '';
    if ($extension === 'svg' || !method_exists($image, 'resize')) {
        return relative_url($image->url());
    }

    $sourceWidth = method_exists($image, 'width') ? (int)$image->width() : 0;
    if ($sourceWidth > 0 && $sourceWidth <= $width) {
        return relative_url($image->url());
    }

    try {
        return relative_url($image->resize($width)->url());
    } catch (Throwable $e) {
        return relative_url($image->url());
    }
};

if ($currentFabric) {
    $otherFabrics = $otherFabrics->filter(fn ($fabric) => $fabric->id() !== $currentFabric->id());
}

if ($otherFabrics->isEmpty()) {
    return;
}
?>
<div class="other-fabrics-section">
    <h2>Другие производители</h2>

    <div class="fabric-grid">
        <?php foreach ($otherFabrics as $fabric): ?>
            <?php
            $kitchens = $fabric->children()->filter(fn ($entry) => $entry->studioPubliclyVisible());
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
                                'url' => relative_url($kuhnya->url()),
                                'title' => (string)$kuhnya->title(),
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
                        'url' => relative_url($kuhnya->url()),
                        'title' => (string)$kuhnya->title(),
                    ];
                }
            }

            if (empty($kitchenSlides)) {
                $kitchenSlides[] = [
                    'image' => $placeholderImageUrl,
                    'url' => $kitchenLinks[0]['url'] ?? relative_url($fabric->url()),
                    'title' => $kitchenLinks[0]['title'] ?? (string)$fabric->title(),
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
                            <?php foreach ($kitchenSlides as $slide): ?>
                                <a
                                    class="fabric-card__media-slide"
                                    href="<?= esc($slide['url'], 'attr') ?>"
                                    aria-label="<?= esc($slide['title'], 'attr') ?>"
                                    style="background-image: url('<?= esc($slide['image'], 'attr') ?>');"
                                ></a>
                            <?php endforeach ?>
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
</div>
