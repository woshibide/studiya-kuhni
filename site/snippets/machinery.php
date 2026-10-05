<?php
$machines = $page->machinery_items()->toStructure();
?>

<section class="machinery-proof" aria-labelledby="machinery-heading">
    <div class="machinery-proof__layout main-grid">
        <div class="machinery-proof__intro">
            <h2 class="machinery-proof__heading" id="machinery-heading"><?= esc($page->machinery_heading()->or('Наше оборудование')) ?></h2>
            <?php if ($page->machinery_lead()->isNotEmpty()): ?>
                <p class="machinery-proof__lead"><?= $page->machinery_lead()->studioText(true) ?></p>
            <?php endif ?>
            <button class="primary-btn" type="button" data-open-nav-contact>связаться с нами</button>
        </div>

        <div class="machinery-proof__cards">
            <?php foreach ($machines as $machine): ?>
                <?php $image = $machine->image()->toFiles()->first(); ?>
                <article class="machinery-proof-card main-grid">
                    <h3 class="machinery-proof-card__name"><?= esc($machine->name()) ?></h3>
                    <figure class="machinery-proof-card__media">
                        <?php snippet('turbo-image', [
                            'image' => $image ?? asset('assets/placeholder.svg'),
                            'alt' => $image ? $image->alt()->or($machine->name())->value() : '',
                            'width' => 720,
                            'loading' => 'lazy',
                        ]) ?>
                    </figure>
                    <div class="machinery-proof-card__facts">
                        <?php foreach ($machine->specifications()->toStructure() as $specification): ?>
                            <p><?= esc($specification->text()) ?></p>
                        <?php endforeach ?>
                    </div>
                    <?php if ($machine->description()->isNotEmpty()): ?>
                        <p class="machinery-proof-card__description"><?= $machine->description()->studioText(true) ?></p>
                    <?php endif ?>
                </article>
            <?php endforeach ?>
        </div>
    </div>
</section>
