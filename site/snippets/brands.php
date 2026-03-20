<section id="brands" class="brands section-full">
    <div class="section-wrapper">
        <?php
        $brandsJson = '[
            {"file": "fabrics-aranCucine.png", "name": "aranCucine"},
            {"file": "fabrics-aster.png", "name": "aster"},
            {"file": "fabrics-homeCucine.png", "name": "homeCucine"},
            {"file": "fabrics-kitchenAid.png", "name": "kitchenAid"},
            {"file": "fabrics-lottocento.png", "name": "lottocento"},
            {"file": "fabrics-lubiex.png", "name": "lubiex"},
            {"file": "fabrics-Kuppersbuch.png", "name": "kuppersbuch"},
            {"file": "fabrics-miele.png", "name": "miele"},
            {"file": "fabrics-mossman.png", "name": "mossman"},
            {"file": "fabrics-neff.png", "name": "neff"},
            {"file": "fabrics-nolte.png", "name": "nolte"},
            {"file": "fabrics-scavolini.png", "name": "scavolini"},
            {"file": "fabrics-smeg.png", "name": "smeg"}
        ]';

        $brandNameRemap = [
            'aranCucine' => 'Aran Cucine',
            'aster' => 'Aster',
            'homeCucine' => 'Home Cucine',
            'kitchenAid' => 'KitchenAid',
            'lottocento' => 'Lottocento',
            'lubiex' => 'Lubiex',
            'kuppersbuch' => 'K&uuml;ppersbuch',
            'miele' => 'Miele',
            'mossman' => 'Mossman',
            'neff' => 'Neff',
            'nolte' => 'Nolte',
            'scavolini' => 'Scavolini',
            'smeg' => 'Smeg'
        ];

        $brands = json_decode($brandsJson, true) ?? [];
        $rows = [[], [], []];

        foreach ($brands as $index => $brand) {
            $rows[$index % 3][] = $brand;
        }

        $formatName = function ($name) use ($brandNameRemap) {
            if (isset($brandNameRemap[$name])) {
                return $brandNameRemap[$name];
            }

            $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
            $name = str_replace(['-', '_'], ' ', $name);
            return ucwords($name);
        };
        ?>
        <div class="marquee-wrapper">
            <?php foreach ($rows as $rowIndex => $rowBrands): ?>
                <?php if (empty($rowBrands)) { continue; } ?>
                <div class="marquee" data-reversed="<?= $rowIndex % 2 === 1 ? 'true' : 'false' ?>">
                    <div class="marquee-track">
                        <div class="marquee-content">
                            <?php foreach ($rowBrands as $brand): ?>
                                <div class="brand-item">
                                    <figure>
                                        <img src="/assets/brands/fabrics/png-white/<?= $brand['file'] ?>" alt="<?= $formatName($brand['name']) ?>" loading="lazy">
                                        <figcaption class="brand-label">
                                            <?= $formatName($brand['name']) ?>
                                        </figcaption>
                                    </figure>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="marquee-content" aria-hidden="true">
                            <?php foreach ($rowBrands as $brand): ?>
                                <div class="brand-item">
                                    <figure>
                                        <img src="/assets/brands/fabrics/png-white/<?= $brand['file'] ?>" alt="<?= $formatName($brand['name']) ?>" loading="lazy">
                                        <figcaption class="brand-label">
                                            <?= $formatName($brand['name']) ?>
                                        </figcaption>
                                    </figure>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
