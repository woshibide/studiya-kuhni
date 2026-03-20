<?php
$kuhnya = $kuhnya ?? $page;

if (!$kuhnya) {
    return;
}

$currentFabric = $kuhnya->parent();

if (!$currentFabric) {
    return;
}

$otherKitchens = $currentFabric
    ->childrenAndDrafts()
    ->filter(fn ($kitchen) => $kitchen->id() !== $kuhnya->id());

if ($otherKitchens->isEmpty()) {
    return;
}
?>
<section class="other-kitchens-section">
    <h2>
        Другие кухни от
        <a href="<?= $currentFabric->url() ?>" class="other-kitchens-section__fabric-link"><?= esc($currentFabric->title()) ?></a>
    </h2>
    <div class="kitchens-grid">
        <?php foreach ($otherKitchens as $kitchen): ?>
            <?php snippet('kuhnya-card-overview', ['kuhnya' => $kitchen, 'showLink' => true]) ?>
        <?php endforeach ?>
    </div>
</section>
