<?php
$benefitsHeading = $page->benefits_heading();
$benefitsItems = $page->benefits_items()->toStructure()->limit(5);
?>

<div class="section-wrapper" id="benefits">
    <?php if ($benefitsHeading->isNotEmpty()): ?>
        <h2><?= esc($benefitsHeading->value()) ?></h2>
    <?php endif ?>
    <div class="benefits-container">
        <?php if ($benefitsItems->isEmpty()): ?>
            <figure>
                <img src="/assets/placeholder.svg" alt="">
                <figcaption>
                    <h3>25 symbols max</h3>
                    <p>180 symbols max</p>
                </figcaption>
            </figure>
        <?php else: ?>
            <?php foreach ($benefitsItems as $item): ?>
                <?php
                $imageFile = $item->image()->toFile();
                $imageUrl = $imageFile ? $imageFile->url() : '/assets/placeholder.svg';
                $imageAlt = $item->alt()->or('');
                $title = $item->title()->or('25 symbols max');
                $text = $item->text()->or('180 symbols max');
                ?>

                <figure>
                    <img src="<?= esc($imageUrl) ?>" alt="<?= esc($imageAlt) ?>">
                    <figcaption>
                        <h3><?= esc($title) ?></h3>
                        <p><?= esc($text) ?></p>
                    </figcaption>
                </figure>
            <?php endforeach ?>
        <?php endif ?>
    </div>
</div>