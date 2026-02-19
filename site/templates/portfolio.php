<?php snippet('header') ?>

<main id="swup" class="transition-fade">
    
    <section>
        <?php snippet('simple-hero') ?>
    </section>

    <section>
        <?php snippet('portfolio-posts', [
            'posts' => $page->children()->listed()->sortBy('date', 'desc'),
            'layout' => 'column'
        ]) ?>
    </section>
    
</main>

<?php snippet('footer') ?>
