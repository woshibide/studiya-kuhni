<?php

/** @var \Kirby\Cms\Block $block */
use Kirby\Toolkit\Html;
use Kirby\Toolkit\Str;

$alt = $block->alt();
$caption = $block->caption();
$crop = $block->crop()->isTrue();
$link = $block->link();
$ratio = $block->ratio()->or('auto');
$imageSource = null;

if ($block->location() == 'web') {
    $imageSource = $block->src()->esc();
} elseif ($imageFile = $block->image()->toFile()) {
    $alt = $alt->or($imageFile->alt());
    $imageSource = $imageFile;
}
?>
<?php if ($imageSource): ?>
<figure<?= Html::attr(['data-ratio' => $ratio, 'data-crop' => $crop], null, ' ') ?>>
  <?php if ($link->isNotEmpty()): ?>
  <a href="<?= Str::esc($link->toUrl()) ?>">
    <?php snippet('turbo-image', [
        'image' => $imageSource,
        'alt' => $alt->esc(),
        'width' => 1600,
        'loading' => 'lazy',
    ]) ?>
  </a>
  <?php else: ?>
  <?php snippet('turbo-image', [
      'image' => $imageSource,
      'alt' => $alt->esc(),
      'width' => 1600,
      'loading' => 'lazy',
  ]) ?>
  <?php endif ?>

  <?php if ($caption->isNotEmpty()): ?>
  <figcaption>
    <?= $caption ?>
  </figcaption>
  <?php endif ?>
</figure>
<?php endif ?>
