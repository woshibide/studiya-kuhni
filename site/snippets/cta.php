<?php
$blueprint = $page->blueprint();
$getDefault = function (string $fieldName) use ($blueprint): string {
    $field = $blueprint->field($fieldName);
    return trim((string)($field['default'] ?? ''));
};

$ctaHeading = trim((string)$page->cta_heading()->value());
$ctaText = trim((string)$page->cta_text()->value());
$ctaButton = trim((string)$page->cta_button()->value());

if ($ctaHeading === '') {
    $ctaHeading = $getDefault('cta_heading');
}

if ($ctaText === '') {
    $ctaText = $getDefault('cta_text');
}

if ($ctaButton === '') {
    $ctaButton = $getDefault('cta_button');
}

$customImage = $page->cta_image()->toFile();
$fallbackImages = [
    'assets/cta/supper1.png',
    'assets/cta/supper2.png',
    'assets/cta/supper3.png'
];
$randomImage = $fallbackImages[array_rand($fallbackImages)];
?>

<section class="section-full">
    <div class="section-wrapper" id="cta">
        <div class="section-sticky">
            <?php if ($ctaHeading !== ''): ?>
                <h2><?= esc($ctaHeading) ?></h2>
            <?php endif ?>
            <?php if ($ctaText !== ''): ?>
                <p><?= esc($ctaText) ?></p>
            <?php endif ?>
            <?php if ($ctaButton !== ''): ?>
                <button type="button" class="primary-btn">
                    <?= esc($ctaButton) ?>
                </button>
            <?php endif ?>
        </div>
        <div class="cta-media">
            <?php if ($customImage): ?>
                <?php snippet('turbo-image', [
                    'image' => $customImage,
                    'alt' => $customImage->alt()->or('')->value(),
                    'width' => 1200,
                    'loading' => 'lazy',
                ]) ?>
            <?php else: ?>
                <?php snippet('turbo-image', [
                    'image' => asset($randomImage),
                    'alt' => 'Call to Action',
                    'width' => 1200,
                    'loading' => 'lazy',
                ]) ?>
            <?php endif; ?>
        </div>
    </div>
</section>