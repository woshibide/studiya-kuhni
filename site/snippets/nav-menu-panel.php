<?php
// Build menu data: Fabrics -> kitchens, then other top-level pages
$fabricsPage = page('fabrics');
$fabrics = [];
if ($fabricsPage && $fabricsPage->children()->isNotEmpty()) {
    foreach ($fabricsPage->children() as $fabric) {
        $kitchens = [];
        if ($fabric->children()->isNotEmpty()) {
            foreach ($fabric->children() as $k) {
                $kitchens[] = ['title' => (string)$k->title(), 'url' => (string)$k->url()];
            }
        }
        $fabrics[] = ['title' => (string)$fabric->title(), 'url' => (string)$fabric->url(), 'kitchens' => $kitchens];
    }
}
$otherLinks = [
    ['title' => 'дизайнерам', 'url' => (page('designers') ? page('designers')->url() : '/designers')],
    ['title' => 'производство', 'url' => (page('proizvodstvo') ? page('proizvodstvo')->url() : '/proizvodstvo')],
    ['title' => 'воспоминания', 'url' => (page('archive') ? page('archive')->url() : '/archive')],
    ['title' => 'faq', 'url' => (page('faq') ? page('faq')->url() : '/faq')],
    ['title' => 'связь', 'url' => (page('contacts') ? page('contacts')->url() : '/contacts')],
    ['title' => 'mediakit', 'url' => (page('mediakit') ? page('mediakit')->url() : '/mediakit')],
    ['title' => 'privacy', 'url' => (page('privacy') ? page('privacy')->url() : '/privacy')],
];
?>

<aside class="nav-contact-panel nav-menu-panel" id="nav-menu-panel" aria-hidden="true" hidden>
    <div class="nav-contact-panel__content">
        <div class="nav-menu-columns">
            <div class="nav-menu-column" aria-labelledby="menu-fabrics">
                <a href="<?= esc($fabricsPage->url(), 'attr') ?>">фабрики</a>
                <ul class="fabrics-list">
                    <?php foreach ($fabrics as $fabric): ?>
                        <li>
                            <a class="hover-underline internal-link__hidden" href="<?= esc($fabric['url'], 'attr') ?>"><?= esc($fabric['title']) ?></a>
                            <?php if (!empty($fabric['kitchens'])): ?>
                                <ul class="fabrics-list sublist">
                                    <?php foreach ($fabric['kitchens'] as $k): ?>
                                        <li><a class="hover-underline internal-link__hidden" href="<?= esc($k['url'], 'attr') ?>"><?= esc($k['title']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="nav-menu-column" aria-labelledby="menu-others">
                <ul>
                    <?php foreach ($otherLinks as $link): ?>
                        <li>
                            <a  class="hover-underline internal-link__hidden"
                                href="<?= esc(relative_url($link['url']), 'attr') ?>"><?= esc($link['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</aside>
