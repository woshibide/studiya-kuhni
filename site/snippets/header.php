<?php
$session = $kirby->session();
$isFirstVisit = !$session->get('visited', false);

if ($isFirstVisit) {
    $session->set('visited', true);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" href="/assets/icons/favicons/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/assets/icons/favicons/favicon32px.png" sizes="32x32" type="image/png">
    <link rel="icon" href="/assets/icons/favicons/favicon16px.png" sizes="16x16" type="image/png">
    <link rel="apple-touch-icon" href="/assets/icons/favicons/favicon180px.png" sizes="180x180">
    <link rel="shortcut icon" href="/assets/icons/favicons/favicon.ico">


    <!-- <?php snippet('seo'); ?> -->
        
    <?= css([
        'assets/css/normalize.css',
        'assets/css/main.css',
        'assets/css/footer.css',
        'assets/css/navbar.css',

        'assets/css/components/cta.css',
        'assets/css/components/benefits.css',
        'assets/css/components/portfolio-posts.css',
        'assets/css/components/gallery.css',
        'assets/css/components/full-hero.css',
        'assets/css/components/faq-section.css',
        'assets/css/components/simple-hero.css'
    ]) ?>

    <?= css('@auto') ?>

    <!-- Yandex.Metrika counter -->
    <!-- Top.Mail.Ru counter -->

    </head>

<body>

<?php snippet('navbar') ?>

<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script> -->
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script> -->


<?php snippet('cookie-consent') ?>
