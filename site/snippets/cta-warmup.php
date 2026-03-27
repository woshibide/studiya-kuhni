<?php
$ctaWarmupHeading = $page->cta_warmup_heading()->or('Нет дизайн плана?');
$ctaWarmupText = $page->cta_warmup_text()->or('Lorem ipsum dolor sit amet consectetur adipiscing elit quisque faucibus ex sapien vitae pellentesque sem placerat in id cursus mi pretium tellus duis convallis tempus leo eu aenean sed diam.');
$ctaWarmupButtonText = $page->cta_warmup_button_text()->or('Получить бесплатную дизайн консультацию');

$customImage = $page->cta_warmup_image()->toFile();
$fallbackImages = [
    'assets/cta-warmup/design1.png',
    'assets/cta-warmup/design2.png',
    'assets/cta-warmup/design4.png',
    'assets/cta-warmup/design5.png'
];
$randomImage = $fallbackImages[array_rand($fallbackImages)];
?>

<div class="section-wrapper" id="cta-warmup">
    <div class="section-sticky">
        <h2><?= esc($ctaWarmupHeading) ?></h2>
        <div class="section-sticky-content">
            <p><?= esc($ctaWarmupText) ?></p>
            <button class="primary-btn" data-open-nav-contact><?= esc($ctaWarmupButtonText) ?></button>
        </div>
    </div>
    <div class="cta-warmup-media">
        <?php if ($customImage): ?>
            <img src="<?= $customImage->url() ?>" alt="<?= esc($customImage->alt()) ?>">
        <?php else: ?>
            <img src="/<?= $randomImage ?>" alt="Design">
        <?php endif; ?>
    </div>

</div>