<?php
if (!function_exists('relative_url')) {
    function relative_url(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $siteUrl = kirby()->url();
        $siteHost = parse_url($siteUrl, PHP_URL_HOST) ?: '';

        if ($siteUrl !== '' && str_starts_with($path, $siteUrl)) {
            $path = substr($path, strlen($siteUrl));
        }

        if (preg_match('~^(?:https?:)?//~i', $path)) {
            $pathHost = parse_url($path, PHP_URL_HOST) ?: '';
            if ($siteHost !== '' && $pathHost === $siteHost) {
                $path = parse_url($path, PHP_URL_PATH) ?: '/';
            } else {
                return $path;
            }
        }

        return '/' . ltrim($path, '/');
    }
}

$siteTitle = Studio\Seo\Metadata::siteTitle($site);
$metaTitle = Studio\Seo\Metadata::title($page);
$metaDescription = Studio\Seo\Metadata::description($page);
$canIndex = studio_indexable() && $page->studioPubliclyVisible() && !$page->seo_noindex()->toBool();
$canonical = Studio\Seo\Metadata::canonical($page);
$shareImage = Studio\Seo\Metadata::image($page);
$needsMap = in_array($page->intendedTemplate()->name(), ['fabric', 'kuhnya'], true) && $page->studioMapLocation() !== null;
if (!$canIndex) $kirby->response()->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" href="<?= esc(relative_url('assets/icons/favicons/favicon.svg'), 'attr') ?>" type="image/svg+xml">
    <link rel="icon" href="<?= esc(relative_url('assets/icons/favicons/favicon32px.png'), 'attr') ?>" sizes="32x32" type="image/png">
    <link rel="icon" href="<?= esc(relative_url('assets/icons/favicons/favicon16px.png'), 'attr') ?>" sizes="16x16" type="image/png">
    <link rel="apple-touch-icon" href="<?= esc(relative_url('assets/icons/favicons/favicon180px.png'), 'attr') ?>" sizes="180x180">
    <link rel="shortcut icon" href="<?= esc(relative_url('assets/icons/favicons/favicon32px.png'), 'attr') ?>" type="image/png">
    
    <?php if ($needsMap): ?>
        <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/js/node_modules/leaflet/dist/leaflet.css'), 'attr') ?>">
    <?php endif ?>
    <title><?= esc($metaTitle . ' | ' . $siteTitle) ?></title>
    <meta name="robots" content="<?= $canIndex ? 'index, follow' : 'noindex, nofollow, noarchive' ?>">
    <meta name="theme-color" content="#f3f4f6">
    <?php if ($metaDescription !== ''): ?>
        <meta name="description" content="<?= esc($metaDescription, 'attr') ?>">
        <meta property="og:description" content="<?= esc($metaDescription, 'attr') ?>">
    <?php endif ?>
    <?php if ($canonical !== '' && $page->studioPubliclyVisible()): ?>
        <link rel="canonical" href="<?= esc($canonical, 'attr') ?>">
        <meta property="og:url" content="<?= esc($canonical, 'attr') ?>">
    <?php endif ?>
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:title" content="<?= esc($metaTitle, 'attr') ?>">
    <meta property="og:site_name" content="<?= esc($siteTitle, 'attr') ?>">
    <?php if ($shareImage): ?>
        <meta property="og:image" content="<?= esc($shareImage['url'], 'attr') ?>">
        <meta property="og:image:alt" content="<?= esc($shareImage['alt'], 'attr') ?>">
        <meta property="og:image:width" content="<?= $shareImage['width'] ?>">
        <meta property="og:image:height" content="<?= $shareImage['height'] ?>">
    <?php endif ?>
    <link rel="preload" href="<?= esc(relative_url('assets/fonts/SuisseIntl-Medium.woff2'), 'attr') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/normalize.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/main.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/footer.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/navbar.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/nav-menu-panel.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/cta.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/gallery.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/benefits.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/full-hero.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/cta-warmup.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/simple-hero.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/faq-section.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/archive-posts.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/other-kitchens.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/brands.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/other-fabrics.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/big-message.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/kuhnya-card-overview.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/fabric-info.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/cookie-consent.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/nav-contact-panel.css'), 'attr') ?>">
    <?php if ($kirby->option('pechente.kirby-admin-bar.active')): ?>
        <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/editor-tools.css'), 'attr') ?>">
    <?php endif ?>

    <?php
    $template = $page->intendedTemplate()->name();
    $cssFile = "assets/css/templates/{$template}.css";
    $cssPath = kirby()->root('index') . '/' . $cssFile;

    if (file_exists($cssPath)): ?>
        <link rel="stylesheet" href="<?= esc(studio_asset_url($cssFile), 'attr') ?>">
    <?php endif ?>

    <link rel="stylesheet" href="<?= esc(studio_asset_url('assets/css/components/callback.css'), 'attr') ?>">

    <!-- Yandex.Metrika counter -->
    <!-- Top.Mail.Ru counter -->

    </head>

<body>

<a class="skip-link" href="#main-content">Перейти к содержимому</a>

<?php snippet('navbar') ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>


<?php snippet('cookie-consent') ?>
