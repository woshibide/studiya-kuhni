<?php
$archivePage = page('archive');
$posts = $posts ?? ($archivePage?->children()?->listed()?->sortBy('date', 'desc')?->limit(3) ?? new Kirby\Cms\Pages());
$layout = $layout ?? 'grid';

if ($layout === 'column') {
    $layoutClass = 'archive-posts-wrapper--column';
} elseif ($layout === 'list') {
    $layoutClass = 'archive-posts-wrapper--list';
} else {
    $layoutClass = 'archive-posts-wrapper--grid';
}

$wrapperClass = 'section-wrapper ' . $layoutClass;
?>

<h2 id="archive-posts__heading">Истории из кухонь, в которых хочется жить</h2>

<div class="<?= esc($wrapperClass) ?>" id="archive-posts">
    <?php foreach ($posts as $post): ?>
        <?php
        $cover = $post->cover()->toFile();
        $imgAsset = $cover ?? asset('assets/placeholder.svg');
        $imgAlt = $cover ? $cover->alt()->or('')->value() : '';
        $title = $post->title()->or('60 symbols max');
        $intro = $post->intro()->or('220 symbols max');
        $kitchen = $post->showcase_kitchen()->toPage();

        $fabric = null;
        if ($kitchen && $kitchen->parent()?->intendedTemplate()?->name() === 'fabric') {
            $fabric = $kitchen->parent();
        }
        ?>

        <div class="archive-posts-card">
            <figure>
                <a href="<?= esc(relative_url($post->url()), 'attr') ?>">
                    <?php snippet('turbo-image', [
                        'image' => $imgAsset,
                        'alt' => $imgAlt,
                        'width' => 960,
                        'loading' => 'lazy',
                    ]) ?>
                </a>
                <figcaption>
                    <?php if ($fabric || $kitchen): ?>
                        <div class="archive-posts-meta">
                            <?php if ($fabric): ?>
                                <a href="<?= esc(relative_url($fabric->url()), 'attr') ?>"><?= esc($fabric->title()) ?></a>
                            <?php endif ?>

                            <?php if ($fabric && $kitchen): ?>
                                <span aria-hidden="true">/</span>
                            <?php endif ?>

                            <?php if ($kitchen): ?>
                                <a href="<?= esc(relative_url($kitchen->url()), 'attr') ?>"><?= esc($kitchen->title()) ?></a>
                            <?php endif ?>
                        </div>
                    <?php endif ?>

                    <a href="<?= esc(relative_url($post->url()), 'attr') ?>" class="archive-posts-title-link">
                        <h3 class="archive-posts-title"><?= esc($title) ?></h3>
                    </a>
                    <p class="archive-posts-intro"><?= esc($intro) ?></p>
                </figcaption>
            </figure>
        </div>
    <?php endforeach ?>

    <?php if ($posts->isEmpty()): ?>
        <p class="archive-posts-empty">постов пока нет</p>
    <?php endif ?>
</div>