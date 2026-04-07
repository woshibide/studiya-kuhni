<?php snippet('header') ?>

<?php
$kitchen = $page->showcase_kitchen()->toPage();
$fabric = $kitchen?->parent();
$cover = $page->cover()->toFile();
$coverUrl = $cover ? $cover->url() : '/assets/placeholder.svg';
$coverAlt = $cover ? $cover->alt()->or($page->title())->value() : $page->title()->value();
$publishedDate = $page->date()->isNotEmpty() ? $page->date()->toDate('d.m.Y') : null;
$location = $page->location()->or('локация не указана');
?>

<main>

    <section class="section-full archive-post-cover" data-archive-post-cover>
        <img src="<?= esc($coverUrl) ?>" alt="<?= esc($coverAlt, 'attr') ?>" data-archive-post-cover-image>
    </section>

    <section class="archive-post-intro">
        <div class="archive-post-intro__header">
            <h1><?= esc($page->title()) ?></h1>

            <p class="archive-post-intro__meta">
                <?php if ($publishedDate): ?>
                    <span><?= esc($publishedDate) ?></span>
                    <span aria-hidden="true">/</span>
                <?php endif ?>
                <span><?= esc($location) ?></span>
                <span aria-hidden="true">/</span>
                <span><?= esc($kitchen?->title()->value() ?? 'кухня не выбрана') ?></span>
                <span aria-hidden="true">/</span>
                <span><?= esc($fabric?->title()->value() ?? 'фабрика не определена') ?></span>
            </p>
        </div>

        <?php if ($page->intro()->isNotEmpty()): ?>
            <p class="archive-post-intro__lead"><?= esc($page->intro()) ?></p>
        <?php endif ?>
    </section>

    <?php if ($page->blocks()->isNotEmpty()): ?>
        <section class="archive-post-blocks portfolio-end-section">
            <?= $page->blocks()->toBlocks() ?>
        </section>
    <?php endif ?>

    <?php if ($kitchen): ?>
        <section class="kuhnya-hero">
            <?php snippet('kuhnya-card-overview', ['kuhnya' => $kitchen, 'showLink' => true]) ?>
        </section>
    <?php endif ?>


    <section>
        <?php snippet('archive-posts', [
            'posts' => $page->siblings()->listed()->sortBy('date', 'desc')->not($page)->limit(3)
        ]) ?>
    </section>

</main>

<?php snippet('footer') ?>
