<?php snippet('header') ?>

<?php
$heroImage = $page->images()->first();
$heroImageUrl = $heroImage ? $heroImage->url() : '/assets/placeholder.svg';
$heroImageAlt = $heroImage ? $heroImage->alt()->or($page->title())->value() : $page->title()->value();
$kuhnyaTitle = trim((string)$page->title()->value());
$fabricPage = $page->parent();
$fabricsIndex = site()->find('fabrics');
$kuhnyaBrand = $fabricPage ? $fabricPage->title()->value() : 'Название фабрики';
$kuhnyaBrandUrl = $fabricPage ? $fabricPage->url() : ($fabricsIndex ? $fabricsIndex->url() : '#');
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

if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    if (mb_strlen($kuhnyaIntro) > 180) {
        $kuhnyaIntro = rtrim(mb_substr($kuhnyaIntro, 0, 180)) . '...';
    }
} elseif (strlen($kuhnyaIntro) > 180) {
    $kuhnyaIntro = rtrim(substr($kuhnyaIntro, 0, 180)) . '...';
}
?>

<main id="swup" class="transition-fade">

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
            <div class="kunya-hero__media" data-kunya-media>
                <img src="<?= esc($heroImageUrl, 'attr') ?>" alt="<?= esc($heroImageAlt, 'attr') ?>" data-kunya-media-image>
            </div>
        </section>

        <section id="about-the-brand">
            <?php snippet('fabric-info') ?>
        </section>

        <section id="details">
            <h2>details</h2>
        </section>

        <section id="table-top">
            <h2>stoleshnitsa</h2>
        </section>

        <section id="materials">
            <h2>materials</h2>
            <h2>handles</h2>
            <h2>oblitsovka</h2>
            <h2>tehnika</h2>
        </section>

        <section>
            <?php snippet('benefits') ?>
        </section>

        <section id="cta">
            <?php snippet('cta') ?>
        </section>
    </div>
    <!-- sticky scope -->

    <section>
        <?php snippet('gallery') ?>
    </section>

    <section>
        <?php snippet('faq-section') ?>
    </section>

    <section>
        <?php snippet('portfolio-posts') ?>
    </section>

    <?php snippet('other-kitchens', ['kuhnya' => $page]) ?>

    <?php snippet('other-fabrics', ['contextPage' => $page]) ?>

</main>

<?php snippet('footer') ?>