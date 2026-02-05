<?php snippet('header') ?>

<main>
  <h1><?= $page->headline()->or($page->title()) ?></h1>
  
  <?php if ($page->text()->isNotEmpty()): ?>
    <div class="faq-text">
      <?= $page->text()->kt() ?>
    </div>
  <?php endif ?>

  <?php snippet('faq-section', ['items' => $page->questions()->toStructure()]) ?>
</main>

<?php snippet('footer') ?>
