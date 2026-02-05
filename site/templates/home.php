<?php snippet('header') ?>

<main>

    <section id="hero">
        hero
    </section>
    <section id="who">
        who
    </section>
    <section id="brands">
        brands
    </section>
    <section id="benefits">
        benefits
    </section>
    <section id="design-solution">
        design-solution
    </section>
    <section id="cta">
        cta
    </section>
    <section id="faq">
        <?php if ($faqPage = page('faq')): ?>
            <?php snippet('faq-section', ['items' => $faqPage->questions()->toStructure()]) ?>
        <?php endif ?>
    </section>
    <section id="blog-posts">
        blog
    </section>
    
</main>

<?php snippet('footer') ?>