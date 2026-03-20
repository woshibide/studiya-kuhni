<nav>
    <div class="nav-left">
        <ul>
            <?php
            $navItems = [
                ['title' => 'фабрики', 'url' => page('fabrics') ? page('fabrics')->url() : '/fabrics'],
                ['title' => 'дизайнерам', 'url' => page('designers') ? page('designers')->url() : '/designers'],
                ['title' => 'производство', 'url' => page('proizvodstvo') ? page('proizvodstvo')->url() : '/proizvodstvo'],
                ['title' => 'портфолио', 'url' => page('portfolio') ? page('portfolio')->url() : '/portfolio'],
            ];

            foreach ($navItems as $item): ?>
                <li>
                    <a href="<?= $item['url'] ?>"><?= $item['title'] ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="nav-center">
        <a href="/">Студия Кухни</a>
    </div>
    <div class="nav-right" data-nav-contact>
        <button
            class="nav-contact-toggle hover-underline"
            type="button"
            aria-expanded="false"
            aria-controls="nav-contact-panel"
        >
            контакты
        </button>
        <?php snippet('nav-contact-panel') ?>
    </div>
</nav>
