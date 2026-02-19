<nav>
    <div class="nav-left">
        <ul>
            <?php foreach ($site->pages() as $page): ?>
                <li>
                    <a href="<?= $page->url() ?>"><?= $page->title() ?></a>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
    <div class="nav-center">
        <a href="/">Студия Кухни</a>
    </div>
    <div class="nav-right">
        Связь
    </div>
</nav>
