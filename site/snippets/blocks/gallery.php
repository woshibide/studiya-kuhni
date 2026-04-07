<?php
/** @var \Kirby\Cms\Block $block */
use Kirby\Toolkit\Html;

$caption = $block->caption();
$crop    = $block->crop()->isTrue();
$ratio   = $block->ratio()->or('auto');
?>
<figure class="archive-post-block-gallery"<?= Html::attr(['data-ratio' => $ratio, 'data-crop' => $crop], null, ' ') ?>>
  <ul class="archive-post-block-gallery__list">
    <?php foreach ($block->images()->toFiles() as $image): ?>
    <li class="archive-post-block-gallery__item">
      <?php snippet('turbo-image', [
          'image' => $image,
          'alt' => $image->alt()->or('')->value(),
          'width' => 1600,
          'loading' => 'lazy',
      ]) ?>
    </li>
    <?php endforeach ?>
  </ul>
  <?php if ($caption->isNotEmpty()): ?>
  <figcaption class="archive-post-block-gallery__caption">
    <?= $caption ?>
  </figcaption>
  <?php endif ?>
</figure>
