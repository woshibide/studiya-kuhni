<?php
$ctaHeading = $page->cta_heading()->or('Call to Action');
$ctaText = $page->cta_text()->or('Оставьте Заявку или Свяжитесь с Нами');
$ctaButton = $page->cta_button()->or('Свяжитесь со мной');
?>

<div class="section-wrapper" id="cta">
    <h2><?= esc($ctaHeading) ?></h2>
    <p><?= esc($ctaText) ?></p>
    <button type="button" class="primary-btn">
        <?= esc($ctaButton) ?>
    </button>
</div>