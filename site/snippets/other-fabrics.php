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
            $firstKitchen = $fabric->childrenAndDrafts()->first();
            $firstKitchenImage = $firstKitchen ? $firstKitchen->images()->first() : null;
            $cardImageUrl = $firstKitchenImage ? $firstKitchenImage->url() : '/assets/placeholder.svg';
            ?>
            <article
                class="fabric-card"
                data-fabric-card
                data-default-image="<?= esc($cardImageUrl, 'attr') ?>"
            >
                <div
                    class="fabric-card__media"
                    style="background-image: url('<?= esc($cardImageUrl, 'attr') ?>');"
                    aria-hidden="true"
                ></div>

                <a class="fabric-card__header" href="<?= $fabric->url() ?>">
                    <h2><?= esc($fabric->title()) ?></h2>
                </a>

                <ul>
                    <?php foreach ($fabric->childrenAndDrafts() as $kuhnya): ?>
                        <?php
                        $kitchenImage = $kuhnya->images()->first();
                        $kitchenImageUrl = $kitchenImage ? $kitchenImage->url() : '/assets/placeholder.svg';
                        ?>
                        <li>
                            <a
                                class="internal-link"
                                href="<?= $kuhnya->url() ?>"
                                data-fabric-image="<?= esc($kitchenImageUrl, 'attr') ?>"
                            >
                                <?= esc($kuhnya->title()) ?>
                            </a>
                        </li>
                    <?php endforeach ?>
                </ul>
            </article>
        <?php endforeach ?>
    </div>
</section>
