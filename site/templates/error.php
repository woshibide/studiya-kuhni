<?php snippet('header') ?>

<main id="main-content" tabindex="-1">
    <section>
        <?php snippet('simple-hero') ?>
    </section>
    <a href="<?= esc(relative_url('/'), 'attr') ?>">вернуться на главную</a>
</main>

<?php snippet('footer') ?>
