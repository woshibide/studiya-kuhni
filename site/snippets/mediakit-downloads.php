<?php

use Kirby\Cms\Files;

if (!isset($files, $sourcePage) || !$files instanceof Files) return;

$downloads = $files->filter(static function ($file) use ($sourcePage): bool {
    if ($file->parent()->id() !== $sourcePage->id() || $file->template() !== 'mediakit-download' || !$file->exists()) {
        return false;
    }

    try {
        return $file->match($file->blueprint()->accept());
    } catch (Kirby\Exception\Exception) {
        return false;
    }
});

if ($downloads->isEmpty()) return;
?>
<ul class="mediakit-downloads" role="list">
    <?php foreach ($downloads as $file): ?>
        <?php
        $label = trim((string)$file->download_label()->value());
        if ($label === '') $label = $file->filename();
        $description = trim((string)$file->download_description()->value());
        ?>
        <li class="mediakit-downloads__item">
            <a class="mediakit-download" href="<?= esc($file->url(), 'attr') ?>" download="<?= esc($file->filename(), 'attr') ?>">
                <span class="mediakit-download__copy">
                    <span class="mediakit-download__label"><?= esc($label) ?></span>
                    <?php if ($description !== ''): ?>
                        <span class="mediakit-download__description"><?= esc($description) ?></span>
                    <?php endif ?>
                    <span class="mediakit-download__meta"><?= esc(strtoupper($file->extension())) ?> · <?= esc($file->niceSize()) ?></span>
                </span>
                <span class="mediakit-download__action">Скачать <span aria-hidden="true">↓</span></span>
            </a>
        </li>
    <?php endforeach ?>
</ul>
