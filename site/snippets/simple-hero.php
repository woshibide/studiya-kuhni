<?php
$heroHeading = $page->simple_hero_heading()->or($page->title());
$heroDescription = $page->simple_hero_description()->or("Lorem ipsum dolor sit amet consectetur adipisicing elit. Quibusdam nulla quod molestias voluptas corporis possimus, beatae velit maxime nam accusantium amet. Vel rerum quae atque temporibus eum, unde dicta ut?");
$heroButtonLabel = $page->simple_hero_button_label();
?>

<div class="section-wrapper" id="simple-hero">
    <h1><?= esc($heroHeading) ?></h1>

    <div class="hero__description">
        <p><?= nl2br(esc($heroDescription)) ?></p>
    </div>

    <?php if ($heroButtonLabel->isNotEmpty()): ?>
        <div class="hero__cta-wrapper">
            <button class="primary-btn" type="button" data-open-nav-contact>
                <?= esc($heroButtonLabel) ?>
            </button>
        </div>
    <?php endif ?>
</div>