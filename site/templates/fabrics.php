<?php snippet('header') ?>
<?php $galleryKitchens = []; ?>

<main id="main-content" class="fabrics-page" tabindex="-1">

    <section>
        <?php snippet('simple-hero') ?>
    </section>

    <section class="fabric-grid" aria-label="Фотографии кухонь">
        <?php foreach ($page->children()->filter(fn ($entry) => $entry->studioPubliclyVisible()) as $fabric): ?>
            <?php
            $kitchens = $fabric->children()->filter(fn ($entry) => $entry->studioPubliclyVisible() && $entry->studioKitchenImages()->isNotEmpty());
            if ($kitchens->isEmpty()) continue;
            $isFirstFabricPhoto = true;
            ?>
            <article class="fabric-grid__fabric" aria-labelledby="fabric-<?= esc($fabric->slug(), 'attr') ?>">
            <?php foreach ($kitchens as $kitchen): ?>
                <?php
                $images = $kitchen->studioKitchenImages();
                $galleryKitchens[] = $kitchen;
                $imageCount = $images->count();
                $imageIndex = 0;
                $kitchenTitle = (string)$kitchen->title();
                $photoTitle = $fabric->title() . ' ' . $kitchenTitle;
                ?>
                <?php foreach ($images as $image): ?>
                    <?php
                    $imageIndex++;
                    $isFirstPhoto = $imageIndex === 1;
                    ?>
                    <?php if ($isFirstFabricPhoto): ?>
                        <h2 class="fabric-grid__fabric-name" id="fabric-<?= esc($fabric->slug(), 'attr') ?>" translate="no"><a class="hover-underline" href="<?= esc(relative_url($fabric->url()), 'attr') ?>"><?= esc($fabric->title()) ?></a></h2>
                    <?php endif ?>
                    <?php if ($isFirstPhoto): ?>
                        <?php
                        $intro = $kitchen->intro()->studioPlainText();
                        $country = trim((string)$kitchen->country_of_origin());
                        $specs = [];
                        foreach ($kitchen->kitchen_specs()->toStructure() as $spec) {
                            $label = trim((string)$spec->label());
                            $value = trim((string)$spec->value());
                            if ($label === '' || $value === '' || preg_match('/цен|стоим|price|cost/iu', $label)) continue;
                            $specs[] = ['label' => $label, 'value' => $value];
                        }
                        ?>
                        <div class="fabric-grid__lead<?= $isFirstFabricPhoto ? ' fabric-grid__lead--fabric-start' : '' ?>" data-fabric-kitchen="<?= esc($kitchen->id(), 'attr') ?>">
                            <article class="fabric-grid__details" aria-label="<?= esc('О кухне ' . $kitchenTitle, 'attr') ?>">
                                <div class="fabric-grid__heading" aria-hidden="true"></div>
                                <div class="fabric-grid__details-body">
                                    <h3 class="fabric-grid__title" translate="no"><a class="hover-underline internal-link" href="<?= esc(relative_url($kitchen->url()), 'attr') ?>"><?= esc($kitchenTitle) ?></a></h3>
                                    <?php if ($intro !== ''): ?>
                                        <p><?= esc($intro) ?></p>
                                    <?php endif ?>
                                    <?php if ($country !== '' || $specs !== []): ?>
                                        <dl data-scroll-reveal>
                                            <?php if ($country !== ''): ?>
                                                <div><dt>Производство</dt><dd><?= esc($country) ?></dd></div>
                                            <?php endif ?>
                                            <?php foreach ($specs as $spec): ?>
                                                <div><dt><?= esc($spec['label']) ?></dt><dd><?= esc($spec['value']) ?></dd></div>
                                            <?php endforeach ?>
                                        </dl>
                                    <?php endif ?>
                                </div>
                            </article>
                    <?php endif ?>
                    <a
                        class="fabric-grid__photo<?= $isFirstPhoto ? ' fabric-grid__photo--first' : '' ?>"
                        data-kitchen-photo="<?= esc($kitchen->id(), 'attr') ?>"
                        data-gallery-catalog-open="<?= esc($image->id(), 'attr') ?>"
                        href="<?= esc(relative_url($kitchen->url()) . '?gallery=' . rawurlencode($image->filename()), 'attr') ?>"
                        aria-haspopup="dialog"
                        aria-label="<?= esc($photoTitle . ', фото ' . $imageIndex . ' из ' . $imageCount, 'attr') ?>"
                    >
                        <div class="fabric-grid__heading" aria-hidden="true"></div>
                        <span class="fabric-grid__image" style="aspect-ratio: <?= max(1, (int)$image->width()) ?> / <?= max(1, (int)$image->height()) ?>">
                            <?php snippet('turbo-image', [
                                'image' => $image,
                                'alt' => $image->alt()->or($photoTitle)->value(),
                                'width' => 1600,
                                'sizes' => 'auto, 100vw',
                            ]) ?>
                        </span>
                    </a>
                    <?php if ($isFirstPhoto): ?>
                        </div>
                    <?php endif ?>
                    <?php $isFirstFabricPhoto = false; ?>
                <?php endforeach ?>
                <?php if ($images->isNotEmpty()): ?>
                    <div class="fabric-grid__kitchen-space" aria-hidden="true"></div>
                <?php endif ?>
            <?php endforeach ?>
            </article>
        <?php endforeach ?>
    </section>

    <?php foreach ($galleryKitchens as $galleryKitchen): ?>
        <?php snippet('gallery', ['page' => $galleryKitchen, 'overlayOnly' => true]) ?>
    <?php endforeach ?>

    <section>
        <?php snippet('big-message') ?>
    </section>

    <section>
        <?php snippet('cta-warmup') ?>
    </section>

    <section>
        <?php snippet('benefits') ?>
    </section>

    <section>
        <?php snippet('faq-section') ?>
    </section>

    <section>
        <?php snippet('cta') ?>
    </section>

    <?php /* archive section hidden for the next launch
    <section>
        <?php snippet('archive-posts') ?>
    </section>
    */ ?>


</main>

<?php snippet('footer') ?>
