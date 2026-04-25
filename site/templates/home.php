<?php snippet('header') ?>

<?php
$heroHeading = trim((string)$page->hero_heading()->value());
if ($heroHeading === '') {
    $heroHeading = 'Кухни ';
}

$heroWords = [];
foreach ($page->hero_words()->toStructure() as $item) {
    $word = trim((string)$item->word()->value());
    if ($word !== '') {
        $heroWords[] = $word;
    }
}

if (empty($heroWords)) {
    $heroWords = ['для вас ', 'на КМВ ', 'для новоселья', 'для влюбленных 💕 ', 'для жизни'];
}

$heroSeoText = $heroHeading . ' ' . implode(', ', $heroWords);

$heroWordsJson = json_encode($heroWords, JSON_UNESCAPED_UNICODE);
if ($heroWordsJson === false) {
    $heroWordsJson = '[]';
}
?>

<main>

    <section>
        <div class="section-wrapper" id="home-hero">
            <h1 >
                <span class="home-hero-prefix"><?= esc($heroHeading) ?></span>
                <span
                    id="home-hero-typed"
                    class="home-hero-typed"
                    data-words="<?= esc($heroWordsJson, 'attr') ?>"
                ><?= esc($heroWords[0]) ?></span>
            </h1>
            <h1 class="home-hero-seo-copy"><?= esc($heroSeoText) ?></h1>
            <div class="hero-description">
                <p>
                    Уже более 00 лет мы привозим и реализуем кухни под ключ на Северном Кавказе, продумывая каждую деталь — от проекта до установки, чтобы вы получили самую удобную и долговечную кухню для жизни.
                </p>
            </div>
            <div class="hero__cta-wrapper">
                <button class="primary-btn" type="button" data-open-nav-contact>Оставить заявку</button>
            </div>
        </div>
    </section>

    <section id="fabrics-intro">
        <?php
        $fabricsPage = page('fabrics');
        $placeholderImageUrl = relative_url('assets/placeholder.svg');
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
        $homeFabricRows = [
            [
                [
                    'slug' => 'aran-cucine',
                    'style' => '--col-start: 4; --col-span: 8;',
                ],
            ],
            [
                [
                    'slug' => 'mossman',
                    'style' => '--col-start: 1; --col-span: 3;',
                ],
            ],
            [
                [
                    'slug' => 'scavolini',
                    'style' => '--col-start: 7; --col-span: 6;',
                ],
            ],
            [
                [
                    'slug' => 'home-cucine',
                    'style' => '--col-start: 1; --col-span: 3;',
                ],
            ],
            [
                [
                    'slug' => 'aster-cucine',
                    'style' => '--col-start: 5; --col-span: 6;',
                ],
            ],
        ];
        ?>

        <div class="fabrics-listing">
            <?php foreach ($homeFabricRows as $row): ?>
                <div class="fabrics-row main-grid" data-home-fabric-row>
                    <?php foreach ($row as $item): ?>
                        <?php
                        $fabric = $fabricsPage ? $fabricsPage->childrenAndDrafts()->findBy('slug', $item['slug']) : null;
                        if (!$fabric) {
                            continue;
                        }

                        $kitchens = $fabric->childrenAndDrafts();
                        $kitchenLinks = [];
                        $kitchenSlides = [];

                        if ($kitchens->count() === 1) {
                            $kitchen = $kitchens->first();
                            if ($kitchen) {
                                $kitchenImages = $resolveKitchenGalleryImages($kitchen);
                                $primaryImage = $kitchenImages->first();
                                $kitchenLinks[] = [
                                    'title' => (string)$kitchen->title(),
                                    'url' => relative_url($kitchen->url()),
                                    'image' => $resolveOptimizedImageUrl($primaryImage, 1200),
                                    'slideIndex' => 0,
                                ];

                                $galleryImages = $kitchenImages->limit(3);
                                if ($galleryImages->isNotEmpty()) {
                                    foreach ($galleryImages as $image) {
                                        $kitchenSlides[] = [
                                            'image' => $resolveOptimizedImageUrl($image, 1600),
                                        ];
                                    }
                                }
                            }
                        } else {
                            foreach ($kitchens as $index => $kitchen) {
                                $kitchenImage = $resolveKitchenGalleryImages($kitchen)->first();
                                $kitchenImageUrl = $resolveOptimizedImageUrl($kitchenImage, 1200);

                                $kitchenLinks[] = [
                                    'title' => (string)$kitchen->title(),
                                    'url' => relative_url($kitchen->url()),
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

                        $defaultImageUrl = $kitchenSlides[0]['image'];
                        ?>

                        <figure
                            class="main-grid-item home-fabric-card"
                            style="<?= esc($item['style'], 'attr') ?>"
                            data-home-fabric-card
                            data-default-image="<?= esc($defaultImageUrl, 'attr') ?>"
                        >
                            <div class="home-fabric-media" data-home-embla>
                                <div class="home-fabric-media__viewport" data-home-embla-viewport>
                                    <div class="home-fabric-media__container">
                                        <?php if (!empty($kitchenSlides)): ?>
                                            <?php foreach ($kitchenSlides as $slide): ?>
                                                <div
                                                    class="home-fabric-media__slide"
                                                    style="background-image: url('<?= esc($slide['image'], 'attr') ?>');"
                                                    aria-hidden="true"
                                                ></div>
                                            <?php endforeach ?>
                                        <?php else: ?>
                                            <div
                                                class="home-fabric-media__slide"
                                                style="background-image: url('<?= esc($defaultImageUrl, 'attr') ?>');"
                                                aria-hidden="true"
                                            ></div>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </div>

                            <button
                                class="home-fabric-toggle"
                                type="button"
                                data-home-fabric-toggle
                                aria-expanded="false"
                                aria-label="Expand fabric card"
                            >
                                <span aria-hidden="true">+</span>
                            </button>

                            <?php if (!empty($kitchenLinks)): ?>
                                <?php $kitchenCount = count($kitchenLinks); ?>
                                <?php $kitchenIndex = 1; ?>
                                <ul class="home-fabric-kitchens">
                                    <?php foreach ($kitchenLinks as $link): ?>
                                        <li style="--kitchen-index: <?= $kitchenIndex ?>; --kitchen-count: <?= $kitchenCount ?>;">
                                            <a
                                                class="internal-link"
                                                href="<?= esc($link['url']) ?>"
                                                data-fabric-image="<?= esc($link['image'], 'attr') ?>"
                                                data-home-slide-index="<?= $link['slideIndex'] ?>"
                                            >
                                                <?= esc($link['title']) ?>
                                            </a>
                                        </li>
                                        <?php $kitchenIndex++; ?>
                                    <?php endforeach ?>
                                </ul>
                            <?php endif ?>

                            <figcaption>
                                <a href="<?= esc(relative_url($fabric->url()), 'attr') ?>">
                                    <?= esc($fabric->title()) ?>
                                </a>
                            </figcaption>
                        </figure>
                    <?php endforeach ?>
                </div>
            <?php endforeach ?>
        </div>
    </section>

    <section>
        <?php snippet('big-message') ?>
    </section>

    <section>
        <?php snippet('brands') ?>
    </section>

    <section>
        <?php snippet('cta-warmup') ?>
    </section>

    <section>
        <?php snippet('benefits') ?>
    </section>

    <?php snippet('cta') ?>

    <section>
        <?php snippet('archive-posts') ?>
    </section>

    <section>
        <?php snippet('faq-section') ?>
    </section>

    
</main>

<?php snippet('footer') ?>