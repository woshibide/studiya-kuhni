<?php snippet('header') ?>

<main>
  
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

  <?php snippet('cta') ?>

</main>

<?php snippet('footer') ?>
