
<?php snippet('header') ?>

<main id="swup" class="transition-fade">

    <section>
        <?php snippet('simple-hero') ?>
    </section>    
    
    <article class="privacy-content">
        <?= $page->text()->kt() ?>
    </article>
    
</main>

<?php snippet('footer') ?>