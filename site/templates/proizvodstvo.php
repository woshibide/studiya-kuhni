<?php snippet('header') ?>

<?php
$singleImage = $page->single_image()->toFile();
$singleImageAlt = $page->single_image_alt();
?>

<main>

    <section>
        <?php snippet('simple-hero') ?>
    </section>

    <section>
        <img
            style="width: 100%;"
            src="<?= $singleImage ? $singleImage->url() : '/assets/placeholder.svg' ?>"
            alt="<?= esc($singleImage ? $singleImageAlt->or($singleImage->alt()) : '') ?>"
        >
    </section>    

    <section>
        <?php snippet ('benefits') ?>
    </section>
    
    <section>
        <?php snippet ('big-message') ?>
    </section>

    <section>
        <?php snippet ('cta-warmup') ?>
    </section>


    <section>
        <?php snippet ('faq-section') ?>
    </section>

</main>

<?php snippet('footer') ?>
