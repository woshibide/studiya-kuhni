<?php snippet('header') ?>

<main id="swup" class="transition-fade">

    <?php
    $resolveKitchenGalleryImages = static function ($kitchen) {
        $selected = $kitchen->kitchen_gallery_images()->toFiles()->filterBy('type', 'image');
        if ($selected->isNotEmpty()) {
            return $selected;
        }

        return $kitchen->images()
            ->filterBy('type', 'image')
            ->filter(function ($image) {
                return strtolower($image->extension()) !== 'svg';
            });
    };
    ?>

    <section>
        <?php snippet('simple-hero') ?>
    </section>

    <section class="fabric-grid">
        <?php foreach ($page->childrenAndDrafts() as $fabric): ?>
            <?php
            $kitchens = $fabric->childrenAndDrafts();
            $kitchenLinks = [];
            $kitchenSlides = [];

            if ($kitchens->count() === 1) {
                $kuhnya = $kitchens->first();
                if ($kuhnya) {
                    $kitchenImages = $resolveKitchenGalleryImages($kuhnya);
                    $primaryImage = $kitchenImages->first();
                    $kitchenLinks[] = [
                        'title' => (string)$kuhnya->title(),
                        'url' => $kuhnya->url(),
                        'image' => $primaryImage ? $primaryImage->url() : '/assets/placeholder.svg',
                        'slideIndex' => 0,
                    ];

                    $galleryImages = $kitchenImages->limit(5);
                    if ($galleryImages->isNotEmpty()) {
                        foreach ($galleryImages as $image) {
                            $kitchenSlides[] = [
                                'image' => $image->url(),
                            ];
                        }
                    }
                }
            } else {
                foreach ($kitchens as $index => $kuhnya) {
                    $kitchenImage = $resolveKitchenGalleryImages($kuhnya)->first();
                    $kitchenImageUrl = $kitchenImage ? $kitchenImage->url() : '/assets/placeholder.svg';

                    $kitchenLinks[] = [
                        'title' => (string)$kuhnya->title(),
                        'url' => $kuhnya->url(),
                        'image' => $kitchenImageUrl,
                        'slideIndex' => $index,
                    ];

                    $kitchenSlides[] = [
                        'image' => $kitchenImageUrl,
                    ];
                }
            }

            if (empty($kitchenSlides)) {
                $kitchenSlides[] = [
                    'image' => '/assets/placeholder.svg',
                ];
            }

            $cardImageUrl = $kitchenSlides[0]['image'];
            ?>
            <div class="fabric-grid__item" data-fabric-item>
                <a class="fabric-card__header" href="<?= $fabric->url() ?>">
                    <h2 class="internal-link__hidden hover-underline"><?= $fabric->title() ?></h2>
                </a>
                <article
                    class="fabric-card"
                    data-fabric-card
                    data-default-image="<?= esc($cardImageUrl, 'attr') ?>"
                >
                    <div class="fabric-card__media" data-fabric-embla>
                        <div class="fabric-card__media-viewport" data-fabric-embla-viewport>
                            <div class="fabric-card__media-container">
                                <?php if (!empty($kitchenSlides)): ?>
                                    <?php foreach ($kitchenSlides as $slide): ?>
                                        <div
                                            class="fabric-card__media-slide"
                                            style="background-image: url('<?= esc($slide['image'], 'attr') ?>');"
                                            aria-hidden="true"
                                        ></div>
                                    <?php endforeach ?>
                                <?php else: ?>
                                    <div
                                        class="fabric-card__media-slide"
                                        style="background-image: url('<?= esc($cardImageUrl, 'attr') ?>');"
                                        aria-hidden="true"
                                    ></div>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                    <ul>
                        <?php foreach ($kitchenLinks as $link): ?>
                            <li>
                                <a
                                    class="internal-link"
                                    href="<?= esc($link['url']) ?>"
                                    data-fabric-image="<?= esc($link['image'], 'attr') ?>"
                                    data-fabric-slide-index="<?= $link['slideIndex'] ?>"
                                >
                                    <?= esc($link['title']) ?>
                                </a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </article>
            </div>
        <?php endforeach ?>
    </section>

    
    <section>
        <?php snippet('big-message') ?>
    </section>

    <section>
        <?php snippet('benefits') ?>
    </section>

    <section>
        <?php snippet('cta') ?>
    </section>

    <section>
        <?php snippet('faq-section') ?>
    </section>

    <section>
        <?php snippet('cta-warmup') ?>
    </section>

    <section>
        <?php snippet('archive-posts') ?>
    </section>


</main>

<?php snippet('footer') ?>