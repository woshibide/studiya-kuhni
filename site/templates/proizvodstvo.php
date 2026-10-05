<?php snippet('header') ?>

<?php
$singleImage = $page->single_image()->toFile();
$singleImageAsset = $singleImage ?? asset('assets/placeholder.svg');
$singleImageAlt = $page->single_image_alt();
?>

<main id="main-content" tabindex="-1">

    <section>
        <?php snippet('simple-hero') ?>
    </section>

    <section>
        <?php snippet('turbo-image', [
            'image' => $singleImageAsset,
            'alt' => $singleImage ? $singleImageAlt->or($singleImage->alt())->value() : '',
            'width' => 2200,
            'loading' => 'eager',
            'class' => 'proizvodstvo-image',
        ]) ?>
    </section>    

    <section>
        <?php snippet ('benefits') ?>
    </section>
    
    <?php snippet('machinery') ?>

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
