<?php
$fabricsPage = page('fabrics');
$fabrics = [];
if ($fabricsPage && $fabricsPage->children()->isNotEmpty()) {
    foreach ($fabricsPage->children() as $fabric) {
        $kitchens = [];
        if ($fabric->children()->isNotEmpty()) {
            foreach ($fabric->children() as $kitchen) {
                $kitchens[] = [
                    'title' => (string)$kitchen->title(),
                    'url' => relative_url((string)$kitchen->url()),
                ];
            }
        }

        $fabrics[] = [
            'title' => (string)$fabric->title(),
            'url' => relative_url((string)$fabric->url()),
            'kitchens' => $kitchens,
        ];
    }
}

$otherLinks = [
    ['title' => 'фабрики', 'url' => page('fabrics') ? relative_url((string)page('fabrics')->url()) : relative_url('/fabrics')],
    ['title' => 'дизайнерам', 'url' => page('designers') ? relative_url((string)page('designers')->url()) : relative_url('/designers')],
    ['title' => 'производство', 'url' => page('proizvodstvo') ? relative_url((string)page('proizvodstvo')->url()) : relative_url('/proizvodstvo')],
    ['title' => 'воспоминания', 'url' => page('archive') ? relative_url((string)page('archive')->url()) : relative_url('/archive')],
    ['title' => 'FAQ', 'url' => page('faq') ? relative_url((string)page('faq')->url()) : relative_url('/faq')],
    ['title' => 'связь', 'url' => page('contacts') ? relative_url((string)page('contacts')->url()) : relative_url('/contacts')],
    ['title' => 'медиа кит', 'url' => page('mediakit') ? relative_url((string)page('mediakit')->url()) : relative_url('/mediakit')],
    ['title' => 'конфиденциальность', 'url' => page('privacy') ? relative_url((string)page('privacy')->url()) : relative_url('/privacy')],
];

$fabricsUrl = $fabricsPage ? relative_url((string)$fabricsPage->url()) : relative_url('/fabrics');
$menuStaggerIndex = 1;
?>

<aside class="nav-menu-panel" id="nav-menu-panel" aria-hidden="true" hidden>
    <div class="nav-menu-panel__content">
        <section class="nav-menu-section nav-menu-section--fabrics" aria-label="фабрики">

            <ul class="nav-menu-list nav-menu-list--fabrics">
                <?php foreach ($fabrics as $fabric): ?>
                    <li class="nav-menu-item">
                        <a
                            class="nav-menu-link __fabric hover-underline internal-link__hidden"
                            style="--menu-stagger-index: <?= $menuStaggerIndex++ ?>"
                            href="<?= esc($fabric['url'], 'attr') ?>"
                        >
                            <?= esc($fabric['title']) ?>
                        </a>

                        <?php if (!empty($fabric['kitchens'])): ?>
                            <ul class="nav-menu-sublist">
                                <?php foreach ($fabric['kitchens'] as $k): ?>
                                    <li>
                                        <a
                                            class="nav-menu-link nav-menu-link--kitchen internal-link__hidden"
                                            style="--menu-stagger-index: <?= $menuStaggerIndex++ ?>"
                                            href="<?= esc($k['url'], 'attr') ?>"
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

        <section class="nav-menu-section nav-menu-section--pages" aria-label="разделы">
            <ul class="nav-menu-list nav-menu-list--pages">
                <?php foreach ($otherLinks as $link): ?>
                    <li>
                        <a
                            class="nav-menu-link internal-link__hidden"
                            style="--menu-stagger-index: <?= $menuStaggerIndex++ ?>"
                            href="<?= esc($link['url'], 'attr') ?>"
                        ><?= esc($link['title']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <p class="nav-menu-meta" style="--menu-stagger-index: <?= $menuStaggerIndex++ ?>">
            <a href="<?= esc(relative_url('/'), 'attr') ?>">Студия Кухни</a>, 2008 — 2026
        </p>
    </div>
</aside>
