<?php
$assetUrl = static fn (string $path): string => url('assets/media-kit/' . implode('/', array_map('rawurlencode', explode('/', $path))));
$logos = [
    ['name' => 'Знак', 'file' => 'studiya_kuhni-mark', 'class' => 'mark'],
    ['name' => 'Основной логотип', 'file' => 'studiya_kuhni-wordmark lock up', 'class' => 'primary'],
    ['name' => 'Горизонтальный логотип', 'file' => 'studiya_kuhni-wordmark long lockup', 'class' => 'wide'],
];
?>
<div class="mediakit-assets">
    <div class="mediakit-logo-grid">
        <?php foreach ($logos as $logo): ?>
            <?php $preview = asset('assets/media-kit/SVG/' . $logo['file'] . '.svg') ?>
            <article class="mediakit-logo mediakit-logo--<?= esc($logo['class'], 'attr') ?>">
                <div class="mediakit-logo__preview">
                    <img src="<?= esc($assetUrl('SVG/' . $logo['file'] . '.svg'), 'attr') ?>" alt="<?= esc($logo['name'] . ' Студии Кухни', 'attr') ?>" width="<?= $preview->width() ?>" height="<?= $preview->height() ?>">
                </div>
                <div class="mediakit-logo__caption">
                    <h3><?= esc($logo['name']) ?></h3>
                    <div class="mediakit-logo__formats" aria-label="Форматы для скачивания">
                        <?php foreach (['SVG', 'PNG', 'JPG'] as $format): ?>
                            <?php $filename = $logo['file'] . '.' . strtolower($format) ?>
                            <a href="<?= esc($assetUrl($format . '/' . $filename), 'attr') ?>" download="<?= esc($filename, 'attr') ?>" aria-label="<?= esc('Скачать ' . $logo['name'] . ' в формате ' . $format, 'attr') ?>"><?= $format ?> <span aria-hidden="true">↓</span></a>
                        <?php endforeach ?>
                    </div>
                </div>
            </article>
        <?php endforeach ?>
    </div>
    <div class="mediakit-asset-files">
        <?php foreach (['media-kit.zip' => 'Скачать весь медиакит', 'studiya_kuhni-logo.ai' => 'Исходник логотипа'] as $filename => $label): ?>
            <?php $asset = asset('assets/media-kit/' . $filename) ?>
            <a class="mediakit-download" href="<?= esc($assetUrl($filename), 'attr') ?>" download="<?= esc($filename, 'attr') ?>">
                <span class="mediakit-download__copy">
                    <span class="mediakit-download__label"><?= esc($label) ?></span>
                    <span class="mediakit-download__meta"><?= esc(strtoupper($asset->extension())) ?> · <?= esc($asset->niceSize()) ?></span>
                </span>
                <span class="mediakit-download__action">Скачать <span aria-hidden="true">↓</span></span>
            </a>
        <?php endforeach ?>
    </div>
</div>
