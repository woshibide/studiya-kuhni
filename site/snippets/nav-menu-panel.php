<?php
$fabricsPage = page('fabrics');
$fabrics = [];
if ($fabricsPage && $fabricsPage->children()->isNotEmpty()) {
    foreach ($fabricsPage->children()->filter(fn ($entry) => $entry->studioPubliclyVisible()) as $fabric) {
        $kitchens = [];
        if ($fabric->children()->isNotEmpty()) {
            foreach ($fabric->children()->filter(fn ($entry) => $entry->studioPubliclyVisible()) as $kitchen) {
                $kitchens[] = [
                    'title' => (string)$kitchen->title(),
                    'url' => relative_url((string)$kitchen->url()),
                    'current' => $kitchen->isActive(),
                ];
            }
        }

        $fabrics[] = [
            'title' => (string)$fabric->title(),
            'url' => relative_url((string)$fabric->url()),
            'kitchens' => $kitchens,
            'current' => $fabric->isActive(),
        ];
    }
}

$otherLinks = [
    ['title' => 'фабрики', 'url' => page('fabrics') ? relative_url((string)page('fabrics')->url()) : relative_url('/fabrics')],
    ['title' => 'дизайнерам', 'url' => page('designers') ? relative_url((string)page('designers')->url()) : relative_url('/designers')],
    ['title' => 'производство', 'url' => page('proizvodstvo') ? relative_url((string)page('proizvodstvo')->url()) : relative_url('/proizvodstvo')],
    // ['title' => 'воспоминания', 'url' => page('archive') ? relative_url((string)page('archive')->url()) : relative_url('/archive')],
    ['title' => 'FAQ', 'url' => page('faq') ? relative_url((string)page('faq')->url()) : relative_url('/faq')],
    ['title' => 'связь', 'url' => page('contacts') ? relative_url((string)page('contacts')->url()) : relative_url('/contacts')],
    ['title' => 'медиа кит', 'url' => page('mediakit') ? relative_url((string)page('mediakit')->url()) : relative_url('/mediakit')],
    ['title' => 'конфиденциальность', 'url' => page('privacy') ? relative_url((string)page('privacy')->url()) : relative_url('/privacy')],
];

$otherLinks = array_filter($otherLinks, static function ($link) {
    $path = trim((string)parse_url($link['url'], PHP_URL_PATH), '/');
    $target = page($path);
    return $target && $target->studioPubliclyVisible();
});

$menuStaggerIndex = 1;
?>

<aside class="nav-menu-panel" id="nav-menu-panel" aria-hidden="true" hidden>
    <div class="nav-menu-panel__content">
        <section class="nav-menu-section nav-menu-section--fabrics" aria-labelledby="nav-menu-catalogue-label">
            <h2 class="nav-menu-label" id="nav-menu-catalogue-label" style="--menu-stagger-index: 0">Кухни по фабрикам</h2>
            <ul class="nav-menu-list nav-menu-list--fabrics">
                <?php foreach ($fabrics as $fabric): ?>
                    <li class="nav-menu-item" style="--menu-stagger-index: <?= $menuStaggerIndex++ ?>">
                        <a
                            class="hover-underline nav-menu-link nav-menu-link--fabric"
                            href="<?= esc($fabric['url'], 'attr') ?>"
                            <?= $fabric['current'] ? 'aria-current="page"' : '' ?>
                            translate="no"
                        >
                            <?= esc($fabric['title']) ?>
                        </a>

                        <?php if (!empty($fabric['kitchens'])): ?>
                            <ul class="nav-menu-sublist">
                                <?php foreach ($fabric['kitchens'] as $kitchenIndex => $k): ?>
                                    <li style="--kitchen-stagger-index: <?= $kitchenIndex ?>">
                                        <a
                                            class="hover-underline nav-menu-link nav-menu-link--kitchen"
                                            href="<?= esc($k['url'], 'attr') ?>"
                                            <?= $k['current'] ? 'aria-current="page"' : '' ?>
                                            translate="no"
                                        >
                                            <?= esc($k['title']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="nav-menu-section nav-menu-section--pages" aria-labelledby="nav-menu-pages-label">
            <h2 class="nav-menu-label" id="nav-menu-pages-label" style="--menu-stagger-index: 0">Студия Кухни</h2>
            <ul class="nav-menu-list nav-menu-list--pages">
                <?php foreach (array_values($otherLinks) as $linkIndex => $link): ?>
                    <li style="--menu-stagger-index: <?= $linkIndex + 1 ?>">
                        <a
                            class="hover-underline nav-menu-link"
                            href="<?= esc($link['url'], 'attr') ?>"
                            <?= $link['url'] === relative_url((string)$page->url()) ? 'aria-current="page"' : '' ?>
                        ><?= esc($link['title']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <p class="nav-menu-meta" style="--menu-stagger-index: <?= $menuStaggerIndex++ ?>">
            <a class="hover-underline" href="<?= esc(relative_url('/'), 'attr') ?>">Студия Кухни</a> <span>2008 - <?= date('Y') ?></span>
        </p>
    </div>
</aside>
