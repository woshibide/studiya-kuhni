<?php snippet('header') ?>

<main id="main-content" tabindex="-1">
	<h1><?= esc($page->title()) ?></h1>
    <?php if ($page->text()->isNotEmpty()): ?>
        <div class="section-wrapper"><?php snippet('article-text', ['text' => $page->text()]) ?></div>
    <?php endif ?>
</main>

<?php snippet('footer') ?>
