
<?php snippet('header') ?>

<main>

    <section>
        <?php snippet('simple-hero') ?>
    </section>    
    
    <article class="privacy-content">
        <?= $page->text()->kt() ?>
    </article>
    
</main>

<?php snippet('footer') ?>