
<?php snippet('header') ?>

<main id="main-content" tabindex="-1">

    <section>
        <?php snippet('simple-hero') ?>
    </section>    
    
    <article class="privacy-content">
        <?php snippet('article-text', ['text' => $page->text()]) ?>
    </article>
    
</main>

<?php snippet('footer') ?>
