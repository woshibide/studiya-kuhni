<?php snippet('header') ?>

<?php
$posts = $page->children()->listed()->sortBy('date', 'desc');
?>

<main id="swup" class="transition-fade">
    <section class="portfolio-layout-sticky-index">
        <div class="section-wrapper">
            <div class="portfolio-layout-sticky-inner">
                <?php if ($posts->isNotEmpty()): ?>
                    <?php foreach ($posts as $post): ?>
                        <?php
                        $kitchen = $post->showcase_kitchen()->toPage();
                        $fabric = $kitchen?->parent();
                        $location = $post->location()->or('локация не указана');
                        ?>
                        <a class="portfolio-layout-index-link" href="#<?= esc($post->slug()) ?>">
                            <?= esc($location) ?> /
                            <?= esc($kitchen?->title()->value() ?? 'кухня не выбрана') ?> /
                            <?= esc($fabric?->title()->value() ?? 'фабрика не определена') ?>
                        </a>
                    <?php endforeach ?>
                <?php else: ?>
                    <p class="portfolio-layout-empty">постов пока нет</p>
                <?php endif ?>
            </div>
        </div>
    </section>

    <section class="portfolio-layout-grid section-wrapper">
        <div class="portfolio-layout-column-spacer" aria-hidden="true"></div>

        <div class="portfolio-layout-feed">
            <?php foreach ($posts as $post): ?>
                <?php
                $cover = $post->cover()->toFile();
                $imgUrl = $cover ? $cover->url() : '/assets/placeholder.svg';
                $imgAlt = $cover ? $cover->alt()->or('')->value() : '';
                $kitchen = $post->showcase_kitchen()->toPage();
                $fabric = $kitchen?->parent();
                $location = $post->location()->or('локация не указана');
                ?>

                <article id="<?= esc($post->slug()) ?>" class="portfolio-layout-item">
                    <h2 class="portfolio-layout-title">
                        <a href="<?= esc($post->url()) ?>"><?= esc($post->title()) ?></a>
                    </h2>

                    <a class="portfolio-layout-media" href="<?= esc($post->url()) ?>">
                        <img src="<?= esc($imgUrl) ?>" alt="<?= esc($imgAlt) ?>">
                    </a>

                    <div class="portfolio-layout-copy">
                        <p class="portfolio-layout-meta">
                            <?= esc($location) ?> /
                            <?= esc($kitchen?->title()->value() ?? 'кухня не выбрана') ?> /
                            <?= esc($fabric?->title()->value() ?? 'фабрика не определена') ?>
                        </p>

                        <?php if ($post->intro()->isNotEmpty()): ?>
                            <p class="portfolio-layout-intro"><?= esc($post->intro()) ?></p>
                        <?php endif ?>
                    </div>
                </article>
            <?php endforeach ?>
        </div>

        <div class="portfolio-layout-column-spacer" aria-hidden="true"></div>
    </section>
</main>

<?php snippet('footer') ?>
