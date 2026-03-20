<?php
$ctaWarmupHeading = $page->cta_warmup_heading()->or('Закажите дизайн решение');
$ctaWarmupText = $page->cta_warmup_text()->or('Lorem ipsum dolor sit amet consectetur adipiscing elit quisque faucibus ex sapien vitae pellentesque sem placerat in id cursus mi pretium tellus duis convallis tempus leo eu aenean sed diam.');
$ctaWarmupButtonText = $page->cta_warmup_button_text()->or('Заказать');
$ctaWarmupButtonLink = $page->cta_warmup_button_link()->or('#');
?>

<div class="section-wrapper" id="cta-warmup">
    <div class="section-sticky">
        <h2><?= esc($ctaWarmupHeading) ?></h2>
        <div class="section-sticky-content">
            <p><?= esc($ctaWarmupText) ?></p>
            <button class="primary-btn" href="<?= esc($ctaWarmupButtonLink) ?>"><?= esc($ctaWarmupButtonText) ?></button>
        </div>
    </div>
    <div class="cta-warmup-media">
        <img src="/assets/placeholder.svg" alt="">
    </div>

</div>