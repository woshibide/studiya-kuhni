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
    $heroWords = ['для вас', 'на КМВ', 'для влюбленных 💕', 'для новоселья', 'для жизни', 'для большооооого счастья', 'чечня купить онлайн'];
}

$heroSeoText = $heroHeading . ' ' . implode(', ', $heroWords);

$heroWordsJson = json_encode($heroWords, JSON_UNESCAPED_UNICODE);
if ($heroWordsJson === false) {
    $heroWordsJson = '[]';
}
?>

<main id="swup" class="transition-fade">

    <section>
        <div class="section-wrapper" id="home-hero">
            <h1 >
                <span class="home-hero-prefix"><?= esc($heroHeading) ?></span>
                <span
                    id="home-hero-typed"
                    class="home-hero-typed"
                    data-words="<?= esc($heroWordsJson, 'attr') ?>"
                ><?= esc($heroWords[0]) ?></span>
                <span id="home-hero-caret" class="home-hero-caret" aria-hidden="true">|</span>
            </h1>
            <h1 class="home-hero-seo-copy"><?= esc($heroSeoText) ?></h1>
            <div class="hero-description">
                <p>
                    Уже более 00 лет мы привозим и реализуем кухни под ключ на Северном Кавказе, продумывая каждую деталь — от проекта до установки, чтобы вы получили максимально удобную и долговечную кухню для жизни.
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
        $homeFabricRows = [
            [
                [
                    'slug' => 'aran-cucine',
                    'style' => '--col-start: 4; --col-span: 8; --col-start-mobile: 1; --col-span-mobile: 12;',
                ],
            ],
            [
                [
                    'slug' => 'mossman',
                    'style' => '--col-start: 1; --col-span: 3; --col-start-mobile: 1; --col-span-mobile: 12;',
                ],
                [
                    'slug' => 'scavolini',
                    'style' => '--col-start: 7; --col-span: 6; --col-start-mobile: 1; --col-span-mobile: 12; margin-top: var(--space-xxl);',
                ],
            ],
            [
                [
                    'slug' => 'home-cucine',
                    'style' => '--col-start: 1; --col-span: 3; --col-start-mobile: 1; --col-span-mobile: 12;',
                ],
                [
                    'slug' => 'aster-cucine',
                    'style' => '--col-start: 4; --col-span: 4; --col-start-mobile: 1; --col-span-mobile: 12; margin-top: var(--space-xxl);',
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
                        $firstKitchen = $kitchens->first();
                        $firstKitchenImage = $firstKitchen ? $firstKitchen->images()->first() : null;
                        $defaultImageUrl = $firstKitchenImage ? $firstKitchenImage->url() : '/assets/placeholder.svg';
                        ?>

                        <figure
                            class="main-grid-item home-fabric-card"
                            style="<?= esc($item['style'], 'attr') ?>"
                            data-home-fabric-card
                            data-default-image="<?= esc($defaultImageUrl, 'attr') ?>"
                        >
                            <div
                                class="home-fabric-media"
                                style="background-image: url('<?= esc($defaultImageUrl, 'attr') ?>');"
                                aria-hidden="true"
                            ></div>

                            <button
                                class="home-fabric-toggle"
                                type="button"
                                data-home-fabric-toggle
                                aria-expanded="false"
                                aria-label="Expand fabric card"
                            >
                                <span aria-hidden="true">+</span>
                            </button>

                            <?php if ($kitchens->isNotEmpty()): ?>
                                <?php $kitchenCount = $kitchens->count(); ?>
                                <?php $kitchenIndex = 1; ?>
                                <ul class="home-fabric-kitchens">
                                    <?php foreach ($kitchens as $kitchen): ?>
                                        <?php
                                        $kitchenImage = $kitchen->images()->first();
                                        $kitchenImageUrl = $kitchenImage ? $kitchenImage->url() : '/assets/placeholder.svg';
                                        ?>
                                        <li style="--kitchen-index: <?= $kitchenIndex ?>; --kitchen-count: <?= $kitchenCount ?>;">
                                            <a
                                                class="internal-link"
                                                href="<?= $kitchen->url() ?>"
                                                data-fabric-image="<?= esc($kitchenImageUrl, 'attr') ?>"
                                            >
                                                <?= esc($kitchen->title()) ?>
                                            </a>
                                        </li>
                                        <?php $kitchenIndex++; ?>
                                    <?php endforeach ?>
                                </ul>
                            <?php endif ?>

                            <figcaption>
                                <a href="<?= $fabric->url() ?>">
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
        <?php snippet('benefits')?>
    </section>

    <section>
        <?php snippet('cta') ?>
    </section>

    <section>
        <?php snippet('faq-section') ?>
    </section>

    <section>
        <?php snippet('portfolio-posts') ?>
    </section>

    
</main>

<?php snippet('footer') ?>