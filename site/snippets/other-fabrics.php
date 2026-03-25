<?php
$contextPage = $contextPage ?? $page;
$currentFabric = $currentFabric ?? null;

if (!$currentFabric && $contextPage) {
    $templateName = $contextPage->intendedTemplate()->name();

    if ($templateName === 'fabric') {
        $currentFabric = $contextPage;
    } elseif ($templateName === 'kuhnya') {
        $currentFabric = $contextPage->parent();
    }
}

$fabricsPage = page('fabrics');

if (!$fabricsPage) {
    return;
}

$otherFabrics = $fabricsPage->childrenAndDrafts();

if ($currentFabric) {
    $otherFabrics = $otherFabrics->filter(fn ($fabric) => $fabric->id() !== $currentFabric->id());
}

if ($otherFabrics->isEmpty()) {
    return;
}
?>
<section class="other-fabrics-section">
    <h2>Другие производители</h2>

    <div class="fabric-grid">
        <?php foreach ($otherFabrics as $fabric): ?>
            <?php
            $kitchens = $fabric->childrenAndDrafts();
            $firstKitchen = $kitchens->first();
            $firstKitchenImage = $firstKitchen ? $firstKitchen->images()->first() : null;
            $cardImageUrl = $firstKitchenImage ? $firstKitchenImage->url() : '/assets/placeholder.svg';
            ?>
            <figure
                class="fabric-card"
                data-fabric-card
                data-default-image="<?= esc($cardImageUrl, 'attr') ?>"
            >
                <div
                    class="fabric-card__media"
                    style="background-image: url('<?= esc($cardImageUrl, 'attr') ?>');"
                    aria-hidden="true"
                ></div>

                <?php if ($kitchens->isNotEmpty()): ?>
                    <?php $kitchenCount = $kitchens->count(); ?>
                    <?php $kitchenIndex = 1; ?>
                    <ul class="fabric-card__kitchens">
                        <?php foreach ($kitchens as $kuhnya): ?>
                            <?php
                            $kitchenImage = $kuhnya->images()->first();
                            $kitchenImageUrl = $kitchenImage ? $kitchenImage->url() : '/assets/placeholder.svg';
                            ?>
                            <li style="--kitchen-index: <?= $kitchenIndex ?>; --kitchen-count: <?= $kitchenCount ?>;">
                                <a
                                    class="internal-link"
                                    href="<?= $kuhnya->url() ?>"
                                    data-fabric-image="<?= esc($kitchenImageUrl, 'attr') ?>"
                                >
                                    <?= esc($kuhnya->title()) ?>
                                </a>
                            </li>
                            <?php $kitchenIndex++; ?>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>

                <figcaption class="fabric-card__caption">
                    <a href="<?= $fabric->url() ?>"><?= esc($fabric->title()) ?></a>
                </figcaption>
            </figure>
        <?php endforeach ?>
    </div>
</section>
