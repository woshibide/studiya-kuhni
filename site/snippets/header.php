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
    <link rel="shortcut icon" href="/assets/icons/favicons/favicon32px.png" type="image/png">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

    <title>
        Кухни КМВ | <?= $page->title() ?>
    </title>
        
    <?= css([
        'assets/css/normalize.css',
        'assets/css/main.css',
        'assets/css/footer.css',
        'assets/css/navbar.css',

        'assets/css/components/cta.css',
        'assets/css/components/gallery.css',
        'assets/css/components/benefits.css',
        'assets/css/components/full-hero.css',
        'assets/css/components/cta-warmup.css',
        'assets/css/components/simple-hero.css',
        'assets/css/components/faq-section.css',
        'assets/css/components/archive-posts.css',
        'assets/css/components/other-kitchens.css',
        'assets/css/components/brands.css',
        'assets/css/components/other-fabrics.css',
        'assets/css/components/big-message.css',
        'assets/css/components/kuhnya-card-overview.css',
        'assets/css/components/fabric-info.css',
        'assets/css/components/cookie-consent.css',
        'assets/css/components/nav-contact-panel.css',
    ]) ?>

    <?= css('@auto') ?>

    <!-- Yandex.Metrika counter -->
    <!-- Top.Mail.Ru counter -->

    </head>

<body>

<?php snippet('navbar') ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>


<?php snippet('cookie-consent') ?>
