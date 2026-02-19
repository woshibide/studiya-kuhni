<?php
$galleryDebug = kirby()->option('debug') === true;

$galleryHeading = 'Галерея';
$galleryAutoplay = false;
$galleryImages = [];

// prefer grouped gallery field if present, but keep legacy fallback for existing content
if ($page->gallery()->isNotEmpty()) {
    $galleryData = $page->gallery()->yaml();
    if (is_array($galleryData)) {
        $galleryHeading = $galleryData['gallery_heading'] ?? $galleryHeading;
        $galleryAutoplay = filter_var(($galleryData['gallery_autoplay'] ?? false), FILTER_VALIDATE_BOOL);

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
    $galleryAutoplay = $page->gallery_autoplay()->toBool();
    $galleryImages = $page->gallery_images()->toFiles()->limit(6)->values();
}

// ensure max 6 and numeric keys for consistent js indexing
$galleryImages = array_values(array_slice($galleryImages, 0, 6));

$fallbackUrl = '/assets/placeholder.svg';
$firstImage = $galleryImages[0] ?? null;
$mainUrl = $firstImage ? $firstImage->url() : $fallbackUrl;
$mainAlt = $firstImage ? $firstImage->alt()->or('')->value() : '';
?>

<div class="section-wrapper" id="gallery">
    <h2><?= esc($galleryHeading) ?></h2>

    <div
        class="gallery"
        data-gallery
        data-gallery-autoplay="<?= $galleryAutoplay ? 'true' : 'false' ?>"
        data-gallery-debug="<?= $galleryDebug ? 'true' : 'false' ?>"
        data-gallery-count="<?= (int)count($galleryImages) ?>"
    >
        <div class="gallery__main" data-gallery-main>
            <img src="<?= esc($mainUrl) ?>" alt="<?= esc($mainAlt) ?>" data-gallery-main-img>
        </div>

        <?php if (count($galleryImages) > 1): ?>
            <div class="gallery__thumbs embla" data-gallery-embla>
                <div class="embla__viewport" data-gallery-embla-viewport>
                    <div class="embla__container">
                        <?php foreach ($galleryImages as $index => $image): ?>
                            <button
                                type="button"
                                class="embla__slide gallery__thumb"
                                data-gallery-thumb
                                data-index="<?= (int)$index ?>"
                            >
                                <img src="<?= esc($image->url()) ?>" alt="<?= esc($image->alt()->or('')->value()) ?>">
                            </button>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>
