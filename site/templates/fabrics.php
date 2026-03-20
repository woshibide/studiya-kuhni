<?php snippet('header') ?>

<main id="swup" class="transition-fade">

    <section>
        <?php snippet('simple-hero') ?>
    </section>

    <section>
        <?php snippet('fabric-info') ?>
    </section>


    <section class="fabric-grid section-full">
        <?php foreach ($page->childrenAndDrafts() as $fabric): ?>
            <?php
            $cardImageUrl = '/assets/placeholder.svg';
            ?>
            <article
                class="fabric-card"
                data-fabric-card
                data-default-image="<?= esc($cardImageUrl, 'attr') ?>"
            >
                <div
                    class="fabric-card__media"
                    style="background-image: url('<?= esc($cardImageUrl, 'attr') ?>');"
                    aria-hidden="true"
                ></div>
                <a class="fabric-card__header" href="<?= $fabric->url() ?>">
                    <h2><?= $fabric->title() ?></h2>
                </a>
                <ul>
                    <?php foreach ($fabric->childrenAndDrafts() as $kuhnya): ?>
                        <?php
                        $kitchenImageUrl = '/assets/placeholder.svg';
                        ?>
                        <li>
                            <a
                                href="<?= $kuhnya->url() ?>"
                                data-fabric-image="<?= esc($kitchenImageUrl, 'attr') ?>"
                            >
                                <?= $kuhnya->title() ?>
                            </a>
                        </li>
                    <?php endforeach ?>
                </ul>
            </article>
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


</main>

<?php snippet('footer') ?>