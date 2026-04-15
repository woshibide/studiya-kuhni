<?php snippet('header') ?>

<?php
$posts = $page->children()->listed()->sortBy('date', 'desc');
?>

<main>


    
    <section class="archive-layout-shell section-wrapper">
        <?php if ($posts->isNotEmpty()): ?>
            <?php foreach ($posts as $post): ?>
                <?php
                $kitchen = $post->showcase_kitchen()->toPage();
                $fabric = $kitchen?->parent();
                $location = $post->location()->or('локация не указана');
                $collab_name = $post->collaborator()->or('@valeriya_dimova');
                $collab_link = $post->collaborator_link()->or('https://instagram.com');
                $cover = $post->cover()->toFile() ?? $post->images()->first();
                $imgAsset = $cover ?? asset('assets/placeholder.svg');
                $imgAlt = $cover ? $cover->alt()->or($post->title()->value())->value() : $post->title()->value();
                $publishedDate = $post->date()->isNotEmpty() ? $post->date()->toDate('d.m.Y') : null;
                ?>

                <article class="archive-post-entry" style="background-image: url('<?= esc($imgAsset->url(), 'attr') ?>');">
                    <div class="archive-post-overlay"></div>
                    <div class="archive-post-content main-grid" onclick="window.location.href='<?= esc(relative_url($post->url()), 'js') ?>';">
                        <div class="archive-post-collab main-grid-item">
                            <h2>Студия Кухни × 
                                <?php if ($collab_link->isNotEmpty()): ?>
                                    <a class="external_link__hidden relative-z" href="<?= esc($collab_link, 'attr') ?>" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();"><?= esc($collab_name) ?></a>
                                <?php else: ?>
                                    <?= esc($collab_name) ?>
                                <?php endif ?>
                            </h2>
                            
                            <div class="archive-layout-visual-meta relative-z" onclick="event.stopPropagation();">
                                <span><?= esc($location) ?></span>
                                <span>/</span>
                                <?php if ($fabric): ?>
                                    <a class="hover-underline" href="<?= esc(relative_url($fabric->url()), 'attr') ?>"><?= esc($fabric->title()->value()) ?></a>
                                <?php else: ?>
                                    <span>—</span>
                                <?php endif ?>
                                <span>/</span>
                                <?php if ($kitchen): ?>
                                    <a class="hover-underline" href="<?= esc(relative_url($kitchen->url()), 'attr') ?>"><?= esc($kitchen->title()->value()) ?></a>
                                <?php else: ?>
                                    <span>—</span>
                                <?php endif ?>
                            </div>

                            <div class="archive-post-infobox relative-z" onclick="window.location.href='<?= esc(relative_url($post->url()), 'js') ?>'; event.stopPropagation();">
                                <h3 class="archive-post-title">
                                    <a class="internal-link__hidden" href="<?= esc(relative_url($post->url()), 'attr') ?>"><?= esc($post->title()) ?></a>
                                </h3>
                                <?php if ($post->intro()->isNotEmpty()): ?>
                                    <p class="archive-layout-intro"><?= esc($post->intro()) ?></p>
                                <?php endif ?>
                                <div class="archive-post-infobox-bottom">
                                    <div class="archive-layout-date">
                                        <?php if ($publishedDate): ?>
                                            <?= esc($publishedDate) ?>
                                        <?php endif ?>
                                    </div>
                                    <button class="primary-btn archive-post-btn" type="button">открыть воспоминание</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach ?>
        <?php else: ?>
            <p class="archive-layout-empty">постов пока нет, но вы возвращайтесь</p>
        <?php endif ?>
    </section>

    <section>
        <?php snippet('big-message') ?>
    </section>

</main>

<?php snippet('footer') ?>
