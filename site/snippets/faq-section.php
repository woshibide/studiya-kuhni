<?php
$faqPage = page('faq');
$items = $items ?? ($faqPage ? $faqPage->questions()->toStructure() : null);

if (!$items || $items->isEmpty()) {
    return;
}
?>
<div class="section-wrapper" id="faq">
    <h2>
        FAQ
    </h2>
    <dl class="faq-list">
        <?php foreach ($items as $item): ?>
            <div class="faq-item">
                <dt class="faq-question">
                    <?php // display question text ?>
                    <?= $item->question() ?>
                </dt>
                <dd class="faq-answer">
                    <?php // display answer content ?>
                    <?= $item->answer() ?>
                </dd>
            </div>
        <?php endforeach ?>
    </dl>
</div>