<?php
$ctaWarmupHeading = $page->cta_warmup_heading()->or('Нужен дизайн план?');
$ctaWarmupText = $page->cta_warmup_text()->or('Lorem ipsum dolor sit amet consectetur adipiscing elit quisque faucibus ex sapien vitae pellentesque sem placerat in id cursus mi pretium tellus duis convallis tempus leo eu aenean sed diam.');
$ctaWarmupButtonText = $page->cta_warmup_button_text()->or('Получить бесплатную дизайн консультацию');

$customImage = $page->cta_warmup_image()->toFile();

?>

<div class="section-wrapper" id="cta-warmup">
    <div class="section-sticky">
        <h2><?= esc($ctaWarmupHeading) ?></h2>
        <div class="section-sticky-content">
            <p><?= $ctaWarmupText->studioText(true) ?></p>
            <button class="primary-btn" data-open-nav-contact><?= esc($ctaWarmupButtonText) ?></button>
        </div>
    </div>
    <div class="cta-warmup-media">
        <?php if ($customImage): ?>
            <?php snippet('turbo-image', [
                'image' => $customImage,
                'alt' => $customImage->alt()->or('')->value(),
                'width' => 1200,
                'loading' => 'lazy',
            ]) ?>
        <?php else: ?>
            <?php snippet('turbo-image', [
                'image' => asset('assets/placeholder.svg'),
                'alt' => '',
                'width' => 1200,
                'loading' => 'lazy',
            ]) ?>
        <?php endif; ?>
    </div>

</div>
