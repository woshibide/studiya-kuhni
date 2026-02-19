<?php snippet('header') ?>

<main id="swup" class="transition-fade">
    
    <section>
        <?php snippet('simple-hero') ?>
    </section>

    
    <?php foreach ($page->childrenAndDrafts() as $fabric): ?>
    <section>
        <a href="<?= $fabric->url() ?>">
            <h2><?= $fabric->title() ?></h2>
        </a>
        <div>
            <ul>
                <?php foreach ($fabric->childrenAndDrafts() as $kuhnya): ?>
                <li>
                    <a href="<?= $kuhnya->url() ?>"><?= $kuhnya->title() ?></a>
                </li>
                <?php endforeach ?>
            </ul>
        </div>
    </section>
    <?php endforeach ?>
</main>

<?php snippet('footer') ?>
