<?php snippet('header') ?>

<main id="main-content" tabindex="-1">
  
  <section>
    <?php snippet('simple-hero') ?>
  </section>

  <?php if ($page->text()->isNotEmpty()): ?>
    <div class="faq-text">
      <?= $page->text()->studioText() ?>
    </div>
  <?php endif ?>

  <section>
    <?php snippet('faq-section') ?>
  </section>

  <?php snippet('cta') ?>

</main>

<?php snippet('footer') ?>
