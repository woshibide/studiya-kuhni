<?php snippet('header') ?>

<main>
    
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
        <?php foreach ($page->childrenAndDrafts() as $kitchen): ?>
            <?php snippet('kuhnya-card-overview', ['kuhnya' => $kitchen, 'showLink' => true]) ?>
        <?php endforeach ?>
    </section>

    <section>
        <?php snippet('archive-posts') ?>
    </section>

    <section>
        <?php snippet('other-fabrics', ['contextPage' => $page, 'currentFabric' => $page]) ?>
    </section>
</main>

<?php snippet('footer') ?>
