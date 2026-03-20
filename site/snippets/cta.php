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
?>

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
        <img src="/assets/placeholder.svg" alt="">
    </div>
</div>