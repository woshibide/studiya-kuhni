<?php snippet('header') ?>

<main id="main-content" tabindex="-1">

    <?php
    $placeholderImageUrl = relative_url('assets/placeholder.svg');
    $resolveKitchenGalleryImages = static fn ($kitchen) => $kitchen->studioKitchenImages();
    $resolveOptimizedImageUrl = static function ($image, int $width = 1600) use ($placeholderImageUrl): string {
        if (!$image || !is_object($image) || !method_exists($image, 'url')) {
            return $placeholderImageUrl;
        }

        $extension = method_exists($image, 'extension') ? strtolower((string)$image->extension()) : '';
        if ($extension === 'svg' || !method_exists($image, 'resize')) {
            return relative_url($image->url());
        }

        $sourceWidth = method_exists($image, 'width') ? (int)$image->width() : 0;
        if ($sourceWidth > 0 && $sourceWidth <= $width) {
            return relative_url($image->url());
        }

        try {
            return relative_url($image->resize($width)->url());
        } catch (Throwable $e) {
            return relative_url($image->url());
        }
    };
    ?>

    <section>
        <?php snippet('simple-hero') ?>
    </section>

    <section class="fabric-grid">
        <?php foreach ($page->children()->filter(fn ($entry) => $entry->studioPubliclyVisible()) as $fabric): ?>
            <?php
            $kitchens = $fabric->children()->filter(fn ($entry) => $entry->studioPubliclyVisible());
            $kitchenLinks = [];
            $kitchenSlides = [];

            if ($kitchens->count() === 1) {
                $kuhnya = $kitchens->first();
                if ($kuhnya) {
                    $kitchenImages = $resolveKitchenGalleryImages($kuhnya);
                    $primaryImage = $kitchenImages->first();
                    $kitchenLinks[] = [
                        'title' => (string)$kuhnya->title(),
                        'url' => relative_url($kuhnya->url()),
                        'image' => $resolveOptimizedImageUrl($primaryImage, 1200),
                        'slideIndex' => 0,
                    ];

                    $galleryImages = $kitchenImages->limit(5);
                    if ($galleryImages->isNotEmpty()) {
                        foreach ($galleryImages as $image) {
                            $kitchenSlides[] = [
                                'image' => $resolveOptimizedImageUrl($image, 1600),
                            ];
                        }
                    }
                }
            } else {
                foreach ($kitchens as $index => $kuhnya) {
                    $kitchenImage = $resolveKitchenGalleryImages($kuhnya)->first();
                    $kitchenImageUrl = $resolveOptimizedImageUrl($kitchenImage, 1200);

                    $kitchenLinks[] = [
                        'title' => (string)$kuhnya->title(),
                        'url' => relative_url($kuhnya->url()),
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
                    'image' => $placeholderImageUrl,
                ];
            }

            $cardImageUrl = $kitchenSlides[0]['image'];
            ?>
            <div class="fabric-grid__item" data-fabric-item>
                <a class="fabric-card__header" href="<?= esc(relative_url($fabric->url()), 'attr') ?>">
                    <h2 class="internal-link__hidden hover-underline"><?= $fabric->title() ?></h2>
                </a>
                <article
                    class="fabric-card"
                    data-fabric-card
                    data-default-image="<?= esc($cardImageUrl, 'attr') ?>"
                >
                    <button type="button" class="fabric-card__toggle" data-fabric-toggle aria-expanded="false" aria-label="Увеличить фотографии <?= esc($fabric->title(), 'attr') ?>">+</button>
                    <div class="fabric-card__media">
                        <div class="fabric-card__media-viewport">
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
        <?php snippet('benefits') ?>
    </section>

    <section>
        <?php snippet('big-message') ?>
    </section>

    <section>
        <?php snippet('cta') ?>
    </section>

    <section>
        <?php snippet('cta-warmup') ?>
    </section>

    <section>
        <?php snippet('faq-section') ?>
    </section>

    <?php /* archive section hidden for the next launch
    <section>
        <?php snippet('archive-posts') ?>
    </section>
    */ ?>


</main>

<?php snippet('footer') ?>