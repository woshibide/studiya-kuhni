<?php snippet('header') ?>

<?php
$singleImage = $page->single_image()->toFile();
$singleImageAsset = $singleImage ?? asset('assets/placeholder.svg');
$singleImageAlt = $page->single_image_alt();
?>

<main>

    <section>
        <?php snippet('simple-hero') ?>
    </section>

    <section>
        <?php snippet('turbo-image', [
            'image' => $singleImageAsset,
            'alt' => $singleImage ? $singleImageAlt->or($singleImage->alt())->value() : '',
            'width' => 2200,
            'loading' => 'eager',
            'attrs' => ['style' => 'width: 100%;'],
        ]) ?>
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
