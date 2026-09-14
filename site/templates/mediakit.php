<?php snippet('header') ?>

<main id="main-content" class="mediakit-page" tabindex="-1">
    
    <section>
        <?php snippet('simple-hero') ?>
    </section>

    <?php foreach (['logos' => 'Логотипы', 'mission' => 'Миссия', 'values' => 'Ценности', 'press' => 'Пресс Кит'] as $section => $fallback): ?>
        <?php
        $heading = $page->content()->get('mediakit_' . $section . '_heading')->or($fallback);
        $text = $page->content()->get('mediakit_' . $section . '_text');
        $files = $page->content()->get('mediakit_' . $section . '_files')->toFiles();
        ?>
        <section class="mediakit-section" aria-labelledby="mediakit-<?= esc($section, 'attr') ?>">
            <h2 id="mediakit-<?= esc($section, 'attr') ?>" class="mediakit-section__heading"><?= esc($heading) ?></h2>
            <div class="mediakit-section__content">
                <?php if ($section === 'logos'): ?>
                    <?php snippet('mediakit-assets') ?>
                <?php endif ?>
                <?php if ($text->isNotEmpty()): ?>
                    <?= $text->studioText(false, 3) ?>
                <?php endif ?>
                <?php snippet('mediakit-downloads', ['files' => $files, 'sourcePage' => $page]) ?>
            </div>
        </section>
    <?php endforeach ?>


</main>

<?php snippet('footer') ?>
