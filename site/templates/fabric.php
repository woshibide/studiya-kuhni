<?php snippet('header') ?>

<main id="swup" class="transition-fade">
    
    <section>
        <?php snippet('simple-hero') ?>
    </section>

    <section>
        <?php snippet('fabric-info') ?>
    </section>

    <section class="kitchens-grid">
        <?php foreach ($page->childrenAndDrafts() as $kitchen): ?>
            <?php snippet('kuhnya-card-overview', ['kuhnya' => $kitchen, 'showLink' => true]) ?>
        <?php endforeach ?>
    </section>

    <section>
        <?php snippet('portfolio-posts') ?>
    </section>

    <section>
        <?php snippet('other-fabrics', ['contextPage' => $page, 'currentFabric' => $page]) ?>
    </section>
</main>

<?php snippet('footer') ?>
