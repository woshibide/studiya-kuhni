<?php snippet('header') ?>

<?php
$kitchen = $page->showcase_kitchen()->toPage();
$fabric = $kitchen?->parent();
$location = $page->location()->or('локация не указана');
?>

<main id="swup" class="transition-fade">

    <section>

    </section>

    <section class="kuhnya-hero">
        <?php snippet('kuhnya-card-overview') ?>
    </section>


    <?php if ($page->intro()->isNotEmpty()): ?>
        <p><?= esc($page->intro()) ?></p>
    <?php endif ?>

    <?php if ($page->blocks()->isNotEmpty()): ?>
        <?= $page->blocks()->toBlocks() ?>
    <?php endif ?>

    <section>
        <?php snippet('portfolio-posts', [
            'posts' => $page->siblings()->listed()->sortBy('date', 'desc')->not($page)->limit(3)
        ]) ?>
    </section>

</main>

<?php snippet('footer') ?>
