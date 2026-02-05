<?php
// faq section using definition list tags
?>
<section class="faq-section">
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
</section>