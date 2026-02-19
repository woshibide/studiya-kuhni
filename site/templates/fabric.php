<?php snippet('header') ?>

<main id="swup" class="transition-fade">
    
    <section>
        <?php snippet('simple-hero') ?>
    </section>

    
    <div class="kitchens-grid">
        <?php foreach ($page->childrenAndDrafts() as $kitchen): ?>
        <article class="kitchen-card">
            <a href="<?= $kitchen->url() ?>">
                <h2><?= $kitchen->title() ?></h2>
            </a>
        </article>
        <?php endforeach ?>
    </div>
    
</main>

<?php snippet('footer') ?>
