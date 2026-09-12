<section id="brands" class="brands section-full">

    <div class="section-wrapper">
        <div id="brands-intro">
            <h2><?= esc($page->brands_heading()->or('Мы привозим на КМВ')) ?></h2>
            <p><?= $page->brands_intro_text()->or('Lorem ipsum dolor sit amet consectetur adipisicing elit. Consectetur vel eius, exercitationem ducimus odio quas doloremque! Saepe tempore, ipsa placeat maiores perspiciatis nesciunt ducimus debitis magnam fugit nisi ea unde.')->studioText(true) ?></p>
        </div>
        <?php
        $brands = [
            ['file' => 'fabrics-AranCucine.svg', 'label' => 'Aran Cucine'],
            ['file' => 'fabrics-Aster.svg', 'label' => 'Aster'],
            ['file' => 'fabrics-HomeCucine.svg', 'label' => 'Home Cucine'],
            ['file' => 'fabrics-KitchenAid.svg', 'label' => 'KitchenAid'],
            ['file' => 'fabrics-lottocento.svg', 'label' => 'Lottocento'],
            ['file' => 'fabrics-Lubiex.svg', 'label' => 'Lubiex'],
            ['file' => 'fabrics-Kuppersbuch.svg', 'label' => 'K&uuml;ppersbuch'],
            ['file' => 'fabrics-Miele.svg', 'label' => 'Miele'],
            ['file' => 'fabrics-Mossman.svg', 'label' => 'Mossman'],
            ['file' => 'fabrics-Neff.svg', 'label' => 'Neff'],
            ['file' => 'fabrics-Nolte.svg', 'label' => 'Nolte'],
            ['file' => 'fabrics-Scavolini.svg', 'label' => 'Scavolini'],
            ['file' => 'fabrics-Smeg.svg', 'label' => 'Smeg'],
        ];

        $brandSource = $page->intendedTemplate()->name() === 'home' ? $page : page('home');
        $customBrands = $brandSource?->brands_items()->toStructure();
        if ($customBrands && $customBrands->isNotEmpty()) {
            $brands = [];
            foreach ($customBrands as $item) {
                $logo = $item->logo()->toFile();
                $name = trim((string)$item->name()->value());
                if (!$logo || $logo->type() !== 'image' || $name === '') continue;
                $brands[] = ['image' => $logo, 'label' => $name];
            }
        } else {
            $brands = array_map(static fn ($brand) => [
                'image' => asset('assets/brands/fabrics/black-svg/' . $brand['file']),
                'label' => html_entity_decode($brand['label'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ], $brands);
        }

        $rows = [[], [], []];

        foreach ($brands as $index => $brand) {
            $rows[$index % 3][] = $brand;
        }

        ?>

        <div class="marquee-wrapper">
            <?php foreach ($rows as $rowIndex => $rowBrands): ?>
                <?php if (empty($rowBrands)) { continue; } ?>
                <div class="marquee" data-direction="<?= $rowIndex % 2 === 0 ? 'ltr' : 'rtl' ?>" data-speed="<?= $rowIndex === 1 ? '96' : '84' ?>">
                    <div class="marqueeInner marquee-track">
                        <?php foreach ($rowBrands as $brand): ?>
                            <div class="brand-item marquee-item">
                                <figure>
                                    <?php snippet('turbo-image', [
                                        'image' => $brand['image'],
                                        'alt' => $brand['label'],
                                        'width' => 360,
                                        'loading' => 'lazy',
                                    ]) ?>
                                    <figcaption class="brand-label">
                                        <?= esc($brand['label']) ?>
                                    </figcaption>
                                </figure>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
