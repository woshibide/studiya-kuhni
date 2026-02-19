<?php
$portfolioPage = page('portfolio');
$posts = $posts ?? ($portfolioPage?->children()?->listed()?->sortBy('date', 'desc')?->limit(3) ?? new Kirby\Cms\Pages());
$layout = $layout ?? 'grid';
$wrapperClass = $layout === 'column' ? 'section-wrapper portfolio-posts-wrapper--column' : 'section-wrapper';
?>

<div class="<?= esc($wrapperClass) ?>" id="portfolio-posts">
    <?php foreach ($posts as $post): ?>
        <?php
        $cover = $post->cover()->toFile();
        $imgUrl = $cover ? $cover->url() : '/assets/placeholder.svg';
        $imgAlt = $cover ? $cover->alt()->or('')->value() : '';
        $title = $post->title()->or('60 symbols max');
        $intro = $post->intro()->or('220 symbols max');
        ?>

        <a class="portfolio-posts-card" href="<?= esc($post->url()) ?>">
            <figure>
                <img src="<?= esc($imgUrl) ?>" alt="<?= esc($imgAlt) ?>">
                <figcaption>
                    <h3 class="portfolio-posts-title"><?= esc($title) ?></h3>
                    <p class="portfolio-posts-intro"><?= esc($intro) ?></p>
                </figcaption>
            </figure>
        </a>
    <?php endforeach ?>
</div>