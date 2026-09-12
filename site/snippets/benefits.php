<?php
$benefitsHeading = $page->benefits_heading();
$benefitsItems = $page->studioBenefits();
if ($benefitsItems->isEmpty()) return;
?>

<div class="section-wrapper" id="benefits">
    <?php if ($benefitsHeading->isNotEmpty()): ?>
        <h2><?= esc($benefitsHeading->value()) ?></h2>
    <?php endif ?>
    <div class="benefits-container">
        <?php foreach ($benefitsItems as $item): ?>
            <?php
            $imageFile = $item->image()->toFile();
            $imageAsset = $imageFile ?? asset('assets/placeholder.svg');
            $imageAlt = $item->alt()->or($imageFile ? $imageFile->alt() : '')->value();
            $title = $item->title();
            $text = $item->text();
            ?>

            <figure>
                <?php snippet('turbo-image', [
                    'image' => $imageAsset,
                    'alt' => $imageAlt,
                    'width' => 640,
                    'loading' => 'lazy',
                ]) ?>
                <figcaption>
                    <h3><?= esc($title) ?></h3>
                    <p><?= $text->studioText(true) ?></p>
                </figcaption>
            </figure>
        <?php endforeach ?>
    </div>
</div>
