<section id="brands" class="brands section-full">

    <div class="section-wrapper">
        <div id="brands-intro">
            <h2>Мы привозим на КМВ</h2>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Consectetur vel eius, exercitationem ducimus odio quas doloremque! Saepe tempore, ipsa placeat maiores perspiciatis nesciunt ducimus debitis magnam fugit nisi ea unde.</p>
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
                                        'image' => asset('assets/brands/fabrics/black-svg/' . $brand['file']),
                                        'alt' => $brand['label'],
                                        'width' => 360,
                                        'loading' => 'lazy',
                                    ]) ?>
                                    <figcaption class="brand-label">
                                        <?= $brand['label'] ?>
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
