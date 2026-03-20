<?php
$portfolioPage = page('portfolio');
$posts = $posts ?? ($portfolioPage?->children()?->listed()?->sortBy('date', 'desc')?->limit(3) ?? new Kirby\Cms\Pages());
$layout = $layout ?? 'grid';

if ($layout === 'column') {
    $layoutClass = 'portfolio-posts-wrapper--column';
} elseif ($layout === 'list') {
    $layoutClass = 'portfolio-posts-wrapper--list';
} else {
    $layoutClass = 'portfolio-posts-wrapper--grid';
}

$wrapperClass = 'section-wrapper ' . $layoutClass;
?>

<div class="<?= esc($wrapperClass) ?>" id="portfolio-posts">
    <?php foreach ($posts as $post): ?>
        <?php
        $cover = $post->cover()->toFile();
        $imgUrl = $cover ? $cover->url() : '/assets/placeholder.svg';
        $imgAlt = $cover ? $cover->alt()->or('')->value() : '';
        $title = $post->title()->or('60 symbols max');
        $intro = $post->intro()->or('220 symbols max');
        $kitchen = $post->showcase_kitchen()->toPage();

        $fabric = null;
        if ($kitchen && $kitchen->parent()?->intendedTemplate()?->name() === 'fabric') {
            $fabric = $kitchen->parent();
        }
        ?>

        <a class="portfolio-posts-card" href="<?= esc($post->url()) ?>">
            <figure>
                <img src="<?= esc($imgUrl) ?>" alt="<?= esc($imgAlt) ?>">
                <figcaption>
                    <?php if ($fabric || $kitchen): ?>
                        <p class="portfolio-posts-meta">
                            <?php if ($fabric): ?>
                                <span><?= esc($fabric->title()) ?></span>
                            <?php endif ?>

                            <?php if ($fabric && $kitchen): ?>
                                <span aria-hidden="true">/</span>
                            <?php endif ?>

                            <?php if ($kitchen): ?>
                                <span><?= esc($kitchen->title()) ?></span>
                            <?php endif ?>
                        </p>
                    <?php endif ?>

                    <h3 class="portfolio-posts-title"><?= esc($title) ?></h3>
                    <p class="portfolio-posts-intro"><?= esc($intro) ?></p>
                </figcaption>
            </figure>
        </a>
    <?php endforeach ?>

    <?php if ($posts->isEmpty()): ?>
        <p class="portfolio-posts-empty">постов пока нет</p>
    <?php endif ?>
</div>