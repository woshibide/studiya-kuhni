<?php snippet('header') ?>

<?php
$posts = $page->children()->listed()->sortBy('date', 'desc');
?>

<main>
    <section class="archive-layout-sticky-index">
        <div class="section-wrapper">
            <div class="archive-layout-sticky-inner">
                <?php if ($posts->isNotEmpty()): ?>
                    <?php foreach ($posts as $post): ?>
                        <?php
                        $kitchen = $post->showcase_kitchen()->toPage();
                        $fabric = $kitchen?->parent();
                        $location = $post->location()->or('локация не указана');
                        ?>
                        <a class="archive-layout-index-link" href="#<?= esc($post->slug()) ?>">
                            <?= esc($location) ?> /
                            <?= esc($kitchen?->title()->value() ?? 'кухня не выбрана') ?> /
                            <?= esc($fabric?->title()->value() ?? 'фабрика не определена') ?>
                        </a>
                    <?php endforeach ?>
                <?php else: ?>
                    <p class="archive-layout-empty">постов пока нет, но вы возвращайтесь</p>
                <?php endif ?>
            </div>
        </div>
    </section>

    <section>
        <?php snippet('simple-hero') ?>
    </section>


    <section class="archive-layout-grid section-wrapper">
        
    <div class="archive-layout-column-spacer" aria-hidden="true"></div>

        <div class="archive-layout-feed">
            <?php foreach ($posts as $post): ?>
                <?php
                $cover = $post->cover()->toFile();
                $imgAsset = $cover ?? asset('assets/placeholder.svg');
                $imgAlt = $cover ? $cover->alt()->or('')->value() : '';
                $kitchen = $post->showcase_kitchen()->toPage();
                $fabric = $kitchen?->parent();
                $location = $post->location()->or('локация не указана');
                ?>

                <article id="<?= esc($post->slug()) ?>" class="archive-layout-item">
                    <h2 class="archive-layout-title">
                        <a class="internal-link__hidden" href="<?= esc(relative_url($post->url()), 'attr') ?>"><?= esc($post->title()) ?></a>
                    </h2>

                    <a class="archive-layout-media" href="<?= esc(relative_url($post->url()), 'attr') ?>">
                        <?php snippet('turbo-image', [
                            'image' => $imgAsset,
                            'alt' => $imgAlt,
                            'width' => 1280,
                            'loading' => 'lazy',
                        ]) ?>
                    </a>

                    <div class="archive-layout-copy">
                        <?php if ($post->intro()->isNotEmpty()): ?>
                            <p class="archive-layout-intro"><?= esc($post->intro()) ?></p>
                        <?php endif ?>
                        <p class="archive-layout-meta">
                            <?= esc($location) ?> /
                            <?= esc($kitchen?->title()->value() ?? 'кухня не выбрана') ?> /
                            <?= esc($fabric?->title()->value() ?? 'фабрика не определена') ?>
                        </p>

                    </div>
                </article>
            <?php endforeach ?>
        </div>

        <div class="archive-layout-column-spacer" aria-hidden="true"></div>
    </section>
</main>

<?php snippet('footer') ?>
