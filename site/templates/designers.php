<?php
$designersProofHeading = $page->designers_proof_heading()->or('С нами работают');
$designersCards = $page->designers_cards()->isNotEmpty() ? $page->designers_cards()->toStructure() : [];
?>

<?php snippet('header') ?>

<main id="main-content" class="designers-page" tabindex="-1">

    <section>
        <?php snippet('simple-hero') ?>
    </section>
    
    <section>
        <?php snippet('brands') ?>
    </section>

    <section>
        <?php snippet('big-message') ?>
    </section>


    <section>
        <?php snippet('benefits') ?>
    </section>
    
    <section class="designers-proof">
        <div class="designers-proof__layout main-grid">
            <div class="designers-proof__intro">
                <h2 class="designers-proof__heading">
                    <?= esc($designersProofHeading) ?>
                </h2>

                <p class="designers-proof__lead"><?= $page->designers_proof_lead()->or('Lorem ipsum dolor sit amet consectetur adipiscing elit quisque faucibus ex sapien vitae pellentesque sem placerat in id cursus mi pretium tellus duis convallis tempus leo eu aenean sed diam.')->studioText(true) ?></p>

                <button class="primary-btn" type="button" data-open-nav-contact>
                    связаться с нами
                </button>
            </div>

            <?php if (!empty($designersCards)): ?>
                <div class="designers-proof__cards">
                    <?php foreach ($designersCards as $card): ?>
                        <?php
                        $image = $card->image()->toFiles()->first();
                        $name = $card->name()->or('Имя дизайнера');
                        $years = $card->years()->isNotEmpty() ? $card->years()->value() : 'N';
                        $projects = $card->projects()->isNotEmpty() ? $card->projects()->value() : 'N';
                        $portfolioUrl = $card->portfolio_url()->toUrl();
                        $description = $card->description()->or('Lorem ipsum dolor sit amet consectetur adipiscing elit quisque faucibus ex sapien vitae pellentesque sem placerat in id cursus mi pretium tellus.');
                        ?>

                        <article class="designers-proof-card main-grid">
                            <h3 class="designers-proof-card__name"><?= esc($name) ?></h3>

                            <figure class="designers-proof-card__media">
                                <?php snippet('turbo-image', [
                                    'image' => $image ?? asset('assets/placeholder.svg'),
                                    'alt' => $image ? $name : '',
                                    'width' => 720,
                                    'loading' => 'lazy',
                                ]) ?>
                            </figure>

                            <div class="designers-proof-card__facts">
                                <p>сотрудничаем <?= esc($years) ?> лет</p>
                                <p>сделали <?= esc($projects) ?> проектов</p>
                                <p>
                                    <?php if (!empty($portfolioUrl)): ?>
                                        <a class="external-link" href="<?= esc($portfolioUrl) ?>" target="_blank" rel="noopener noreferrer">портфолио</a>
                                    <?php else: ?>
                                        <span>портфолио</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <p class="designers-proof-card__description"><?= $description->studioText(true) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <section class="designers-work" aria-labelledby="designers-work-heading">
        <div class="designers-work__intro">
            <h2 id="designers-work-heading"><?= esc($page->designers_work_heading()->or('Как мы работаем')) ?></h2>
            <?php if ($page->designers_work_text()->isNotEmpty()): ?>
                <?= $page->designers_work_text()->studioText(false, 3) ?>
            <?php endif ?>
        </div>
        <?php if ($page->designers_work_steps()->isNotEmpty()): ?>
            <ol class="designers-work__steps" role="list">
                <?php foreach ($page->designers_work_steps()->toStructure() as $step): ?>
                    <li class="designers-work__step">
                        <h3><?= esc($step->heading()) ?></h3>
                        <?php if ($step->text()->isNotEmpty()): ?>
                            <?= $step->text()->studioText(false, 4) ?>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ol>
        <?php endif ?>
    </section>
        
    <section>
        <?php snippet('faq-section') ?>
    </section>



</main>

<?php snippet('footer') ?>
