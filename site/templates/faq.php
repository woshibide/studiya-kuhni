<?php snippet('header') ?>

<main id="swup" class="transition-fade">
  
  <section>
      <?php snippet('simple-hero') ?>
  </section>

  
  <?php if ($page->text()->isNotEmpty()): ?>
    <div class="faq-text">
      <?= $page->text()->kt() ?>
    </div>
  <?php endif ?>

  <section>
    <?php snippet('faq-section') ?>
  </section>

</main>

<?php snippet('footer') ?>
