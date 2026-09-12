<?php snippet('header') ?>

<main id="main-content" tabindex="-1">
    
    <section>
        <?php snippet('simple-hero') ?>
    </section>

    <section>
        <?php snippet('fabric-info') ?>
    </section>

    <section>
        <?php snippet('big-message') ?>
    </section>


    <section class="kitchens-grid">
        <?php foreach ($page->children()->filter(fn ($entry) => $entry->studioPubliclyVisible()) as $kitchen): ?>
            <?php snippet('kuhnya-card-overview', ['kuhnya' => $kitchen, 'showLink' => true]) ?>
        <?php endforeach ?>
    </section>

    <?php /* archive section hidden for the next launch
    <section>
        <?php snippet('archive-posts') ?>
    </section>
    */ ?>

    <section>
        <?php snippet('other-fabrics', ['contextPage' => $page, 'currentFabric' => $page]) ?>
    </section>
</main>

<?php snippet('footer') ?>
