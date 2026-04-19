<?php snippet('header') ?>

<?php
$heroGalleryImages = $page->kitchen_gallery_images()->toFiles()->filterBy('type', 'image');
if ($heroGalleryImages->isEmpty()) {
    $heroGalleryImages = $page->images()
        ->filterBy('type', 'image')
        ->filter(function ($image) {
            return strtolower($image->extension()) !== 'svg';
        });
}

$heroSlides = [];
foreach ($heroGalleryImages as $heroGalleryImage) {
    $heroSlides[] = [
        'image' => $heroGalleryImage,
        'alt' => $heroGalleryImage->alt()->or($page->title())->value(),
    ];
}

if (empty($heroSlides)) {
    $heroSlides[] = [
        'image' => asset('assets/placeholder.svg'),
        'alt' => $page->title()->value(),
    ];
}

$heroSlideCount = count($heroSlides);
$heroHasMultipleSlides = $heroSlideCount > 1;
$kuhnyaLayoutImages = $heroGalleryImages->limit(6);
$kuhnyaImageSlots = [
    [
        'style' => '--col-start: 7; --col-span: 6; --col-start-mobile: 1; --col-span-mobile: 12;',
    ],
    [
        'style' => '--col-start: 1; --col-span: 3; --col-start-mobile: 1; --col-span-mobile: 12;',
    ],
    [
        'style' => '--col-start: 5; --col-span: 7; --col-start-mobile: 1; --col-span-mobile: 12; margin-top: calc(var(--space-xxl) * 1.5);',
    ],
    [
        'style' => '--col-start: 1; --col-span: 3; --col-start-mobile: 1; --col-span-mobile: 12;',
    ],
    [
        'style' => '--col-start: 8; --col-span: 5; --col-start-mobile: 1; --col-span-mobile: 12; margin-top: var(--space-xxl);',
    ],
    [
        'style' => '--col-start: 1; --col-span: 7; --col-start-mobile: 1; --col-span-mobile: 12;',
    ],
];
$kuhnyaTitle = trim((string)$page->title()->value());
$fabricPage = $page->parent();
$fabricsIndex = site()->find('fabrics');
$kuhnyaBrand = $fabricPage ? $fabricPage->title()->value() : 'Название фабрики';
$kuhnyaBrandUrl = relative_url($fabricPage ? $fabricPage->url() : ($fabricsIndex ? $fabricsIndex->url() : '#'));
$kuhnyaBlueprint = $page->blueprint();
$kuhnyaFieldDefault = function (string $fieldName) use ($kuhnyaBlueprint): string {
    $field = $kuhnyaBlueprint->field($fieldName);
    return trim((string)($field['default'] ?? ''));
};

$kuhnyaIntro = trim((string)$page->intro()->value());
$kuhnyaCountry = trim((string)$page->country_of_origin()->value());
$kuhnyaPrice = trim((string)$page->price()->value());

if ($kuhnyaIntro === '') {
    $kuhnyaIntro = $kuhnyaFieldDefault('intro');
}

if ($kuhnyaCountry === '') {
    $kuhnyaCountry = $kuhnyaFieldDefault('country_of_origin');
}

if ($kuhnyaPrice === '') {
    $kuhnyaPrice = $kuhnyaFieldDefault('price');
}

$kuhnyaSpecs = $page->kitchen_specs()->toStructure();
$kuhnyaFeatures = $page->kitchen_features()->toStructure();
$hasOtherKitchens = $fabricPage
    ? $fabricPage
        ->childrenAndDrafts()
        ->filter(fn ($kitchen) => $kitchen->id() !== $page->id())
        ->isNotEmpty()
    : false;

if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    if (mb_strlen($kuhnyaIntro) > 180) {
        $kuhnyaIntro = rtrim(mb_substr($kuhnyaIntro, 0, 180)) . '...';
    }
} elseif (strlen($kuhnyaIntro) > 180) {
    $kuhnyaIntro = rtrim(substr($kuhnyaIntro, 0, 180)) . '...';
}
?>

<main>

    <section id="kuhnya-intro">
        <h1><?= $page->title() ?></h1>
        <!-- <div class="kuhnya-intro__meta">
            <p><?= esc($kuhnyaBrand) ?></p>
            <p><?= esc($kuhnyaCountry) ?></p>
        </div> -->
    </section>

    <!-- sticky scope -->
    <div class="kunya-sticky-scope">
        <div class="kunya-sticky-overview" data-kunya-sticky-overview>
            <div class="kunya-sticky-overview__inner">
                <article class="kunya-sticky-overview__content" aria-label="<?= esc($kuhnyaTitle, 'attr') ?>">
                    <a class="kunya-sticky-overview__eyebrow" href="<?= esc($kuhnyaBrandUrl, 'attr') ?>"><?= esc($kuhnyaBrand) ?></a>
                    <h2 class="kunya-sticky-overview__title"><?= esc($kuhnyaTitle) ?></h2>
                    <p class="kunya-sticky-overview__intro"><?= esc($kuhnyaIntro) ?></p>

                    <ul class="kunya-sticky-overview__facts">
                        <li class="kunya-sticky-overview__fact-item">
                            <span class="kunya-sticky-overview__fact-value"><?= esc($kuhnyaCountry) ?></span>
                        </li>
                        <li class="kunya-sticky-overview__fact-item kunya-sticky-overview__fact-item--price">
                            <span class="kunya-sticky-overview__fact-value"><?= esc($kuhnyaPrice) ?></span>
                            <button class="kunya-sticky-overview__cta hover-underline" type="button" data-open-nav-contact>
                                узнать подробности
                            </button>
                        </li>
                        <?php foreach ($kuhnyaSpecs as $spec): ?>
                            <?php
                            $specLabel = trim($spec->label()->value());
                            $specValue = trim($spec->value()->value());
                            if ($specLabel === '' && $specValue === '') {
                                continue;
                            }
                            ?>
                            <li class="kunya-sticky-overview__fact-item">
                                <span class="kunya-sticky-overview__fact-label"><?= esc($specLabel !== '' ? $specLabel : 'detail') ?></span>
                                <span class="kunya-sticky-overview__fact-value"><?= esc($specValue) ?></span>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </article>
            </div>
        </div>


        <section id="kunya-hero" class="section-full" data-kunya-hero>
            <div class="kunya-hero__shell">
                <?php if ($heroHasMultipleSlides): ?>
                    <button class="kunya-hero__arrow kunya-hero__arrow--prev" type="button" data-kunya-hero-prev aria-label="Previous kitchen image">
                        <span aria-hidden="true">←</span>
                    </button>
                    <button class="kunya-hero__arrow kunya-hero__arrow--next" type="button" data-kunya-hero-next aria-label="Next kitchen image">
                        <span aria-hidden="true">→</span>
                    </button>
                <?php endif ?>

                <div class="kunya-hero__media" data-kunya-media data-kunya-hero-viewport aria-roledescription="carousel" aria-label="Kitchen gallery">
                    <div class="kunya-hero__container">
                        <?php foreach ($heroSlides as $heroIndex => $heroSlide): ?>
                            <div
                                class="kunya-hero__slide"
                                data-kunya-hero-slide
                                aria-label="<?= esc(($heroIndex + 1) . ' of ' . $heroSlideCount, 'attr') ?>"
                            >
                                <?php snippet('turbo-image', [
                                    'image' => $heroSlide['image'],
                                    'alt' => $heroSlide['alt'],
                                    'width' => 2200,
                                    'loading' => $heroIndex === 0 ? 'eager' : 'lazy',
                                    'attrs' => ['data-kunya-hero-image' => true],
                                ]) ?>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        </section>

        <section id="features">
            <?php if ($kuhnyaFeatures->isNotEmpty()): ?>
                <ul class="kuhnya-features-list">
                    <?php foreach ($kuhnyaFeatures as $feature): ?>
                        <?php
                        $featureText = trim($feature->text()->value());
                        $featureImage = $feature->image()->toFile();
                        if ($featureText === '' && !$featureImage) {
                            continue;
                        }
                        ?>
                        <li class="kuhnya-features-list__item">
                            <?php if ($featureImage): ?>
                                <?php snippet('turbo-image', [
                                    'image' => $featureImage,
                                    'class' => 'kuhnya-features-list__image',
                                    'alt' => $featureImage->alt()->or($featureText)->or($kuhnyaTitle)->value(),
                                    'width' => 960,
                                    'loading' => 'lazy',
                                ]) ?>
                            <?php endif ?>
                            <?php if ($featureText !== ''): ?>
                                <p class="kuhnya-features-list__text"><?= esc($featureText) ?></p>
                            <?php endif ?>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        </section>

        <section id="about-the-brand">
            <?php snippet('fabric-info') ?>
        </section>        

        <section id="details">
            <?php if ($kuhnyaLayoutImages->isNotEmpty()): ?>
                <div class="kuhnya-layout-grid main-grid">
                    <?php $layoutSlotIndex = 0; ?>
                    <?php foreach ($kuhnyaLayoutImages as $layoutImage): ?>
                        <?php
                        $slot = $kuhnyaImageSlots[$layoutSlotIndex] ?? null;
                        if (!$slot) {
                            continue;
                        }
                        $layoutAlt = $layoutImage->alt()->or($page->title())->value();
                        ?>
                        <figure
                            class="main-grid-item kuhnya-layout-card"
                            style="<?= esc($slot['style'], 'attr') ?>"
                            data-kuhnya-layout-card
                        >
                            <div class="kuhnya-layout-media">
                                <?php snippet('turbo-image', [
                                    'image' => $layoutImage,
                                    'alt' => $layoutAlt,
                                    'width' => 1600,
                                    'loading' => 'lazy',
                                ]) ?>
                            </div>

                            <button
                                class="kuhnya-layout-toggle"
                                type="button"
                                data-kuhnya-layout-toggle
                                aria-expanded="false"
                                aria-label="Expand image"
                            >
                                <span aria-hidden="true">+</span>
                            </button>
                        </figure>
                        <?php $layoutSlotIndex++; ?>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>


        <section id="benefits">
            <?php snippet('benefits') ?>
        </section>

        <!-- 
        <section id="table-top">
            <h2>stoleshnitsa</h2>
        </section>

        <section id="materials">
            <h2>materials</h2>
            <h2>handles</h2>
            <h2>oblitsovka</h2>
        </section> -->

        <section id="cta">
            <?php snippet('cta') ?>
        </section>
    </div>
    <!-- sticky scope -->

    <section>
        <!-- only for kitchens -->
        <?php snippet('gallery') ?>
    </section>

    <?php if ($hasOtherKitchens): ?>
        <section>
            <?php snippet('other-kitchens', ['kuhnya' => $page]) ?>
        </section>
    <?php endif ?>

    <section>
        <?php snippet('other-fabrics', ['contextPage' => $page]) ?>
    </section>

    <section>
        <?php snippet('archive-posts') ?>
    </section>

    <section>
        <?php snippet('faq-section') ?>
    </section>


</main>

<?php snippet('footer') ?>