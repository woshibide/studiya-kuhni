<?php snippet('header') ?>

<main id="swup" class="transition-fade">

    <section>
        <?php snippet('simple-hero') ?>
    </section>


    <?php if ($page->intro()->isNotEmpty()): ?>
        <p><?= esc($page->intro()) ?></p>
    <?php endif ?>

    <?php if ($page->blocks()->isNotEmpty()): ?>
        <?= $page->blocks()->toBlocks() ?>
    <?php endif ?>

    <section>
        <?php snippet('portfolio-posts') ?>
    </section>

</main>

<?php snippet('footer') ?>
