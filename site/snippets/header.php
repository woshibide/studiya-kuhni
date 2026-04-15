<?php
$session = $kirby->session();
$isFirstVisit = !$session->get('visited', false);

if (!function_exists('relative_url')) {
    function relative_url(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $siteUrl = kirby()->url();
        $siteHost = parse_url($siteUrl, PHP_URL_HOST) ?: '';
        $basePath = kirby()->option('jr.static_site_generator.base_url', '/');
        $basePath = '/' . trim((string)$basePath, '/') . '/';
        if ($basePath === '//') {
            $basePath = '/';
        }

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

        $normalizedPath = '/' . ltrim($path, '/');
        if ($basePath !== '/' && str_starts_with($normalizedPath, $basePath)) {
            $normalizedPath = substr($normalizedPath, strlen(rtrim($basePath, '/')));
            if ($normalizedPath === '' || $normalizedPath === false) {
                $normalizedPath = '/';
            }
        }

        $relativePath = ltrim($normalizedPath, '/');

        if ($relativePath === '') {
            return $basePath;
        }

        return $basePath . $relativePath;
    }
}

if ($isFirstVisit) {
    $session->set('visited', true);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" href="<?= esc(relative_url('assets/icons/favicons/favicon.svg'), 'attr') ?>" type="image/svg+xml">
    <link rel="icon" href="<?= esc(relative_url('assets/icons/favicons/favicon32px.png'), 'attr') ?>" sizes="32x32" type="image/png">
    <link rel="icon" href="<?= esc(relative_url('assets/icons/favicons/favicon16px.png'), 'attr') ?>" sizes="16x16" type="image/png">
    <link rel="apple-touch-icon" href="<?= esc(relative_url('assets/icons/favicons/favicon180px.png'), 'attr') ?>" sizes="180x180">
    <link rel="shortcut icon" href="<?= esc(relative_url('assets/icons/favicons/favicon32px.png'), 'attr') ?>" type="image/png">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

    <title>
        Кухни КМВ | <?= $page->title() ?>
    </title>

    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/normalize.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/main.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/footer.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/navbar.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/cta.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/gallery.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/benefits.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/full-hero.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/cta-warmup.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/simple-hero.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/faq-section.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/archive-posts.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/other-kitchens.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/brands.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/other-fabrics.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/big-message.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/kuhnya-card-overview.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/fabric-info.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/cookie-consent.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(relative_url('assets/css/components/nav-contact-panel.css'), 'attr') ?>">

    <?php
    $template = $page->intendedTemplate()->name();
    $cssFile = "assets/css/templates/{$template}.css";
    $cssPath = kirby()->root('index') . '/' . $cssFile;

    if (file_exists($cssPath)): ?>
        <link rel="stylesheet" href="<?= esc(relative_url($cssFile), 'attr') ?>">
    <?php endif ?>

    <!-- Yandex.Metrika counter -->
    <!-- Top.Mail.Ru counter -->

    </head>

<body>

<?php snippet('navbar') ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>


<?php snippet('cookie-consent') ?>
