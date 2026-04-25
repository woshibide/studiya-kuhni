<?php
$faqPage = page('faq');
$items = $items ?? ($faqPage ? $faqPage->questions()->toStructure() : null);

if (!$items || $items->isEmpty()) {
    return;
}

$tabs = [
    'general' => 'Общие',
    'delivery' => 'Доставка',
    'installation' => 'Установка',
    'care' => 'Гарантии',
];

$groupedItems = [];
foreach ($tabs as $slug => $label) {
    $groupedItems[$slug] = [];
}

$faqEntities = [];

foreach ($items as $item) {
    $question = trim((string)$item->question());
    $answerHtml = (string)$item->answer()->kt();
    $answerText = trim(preg_replace('/\s+/u', ' ', strip_tags($answerHtml)));
    $targetTab = trim((string)$item->category());

    if (array_key_exists($targetTab, $tabs) === false) {
        $targetTab = 'general';
    }

    $groupedItems[$targetTab][] = $item;

    if ($question !== '' && $answerText !== '') {
        $faqEntities[] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answerText,
            ],
        ];
    }
}

$firstTabSlug = array_key_first($tabs);
?>
<div class="section-wrapper" id="faq">
    <h2>
        FAQ
    </h2>
    <div class="faq-section-content">
        <div class="faq-category-tabs" role="tablist" aria-label="Категории FAQ">
            <?php foreach ($tabs as $tabSlug => $tabLabel): ?>
                <?php
                $tabId = 'faq-tab-' . $tabSlug;
                $panelId = 'faq-panel-' . $tabSlug;
                $isActive = $tabSlug === $firstTabSlug;
                ?>
                <button
                    id="<?= $tabId ?>"
                    class="faq-category-tab"
                    type="button"
                    role="tab"
                    aria-controls="<?= $panelId ?>"
                    aria-selected="<?= $isActive ? 'true' : 'false' ?>"
                    tabindex="<?= $isActive ? '0' : '-1' ?>"
                    data-faq-tab="<?= $tabSlug ?>"
                >
                    <?= $tabLabel ?>
                </button>
            <?php endforeach ?>
        </div>

        <?php foreach ($tabs as $tabSlug => $tabLabel): ?>
            <?php
            $panelId = 'faq-panel-' . $tabSlug;
            $tabId = 'faq-tab-' . $tabSlug;
            $isActive = $tabSlug === $firstTabSlug;
            ?>
            <div
                id="<?= $panelId ?>"
                class="faq-category-panel"
                role="tabpanel"
                aria-labelledby="<?= $tabId ?>"
                data-faq-panel="<?= $tabSlug ?>"
                <?= $isActive ? '' : 'hidden' ?>
            >
                <?php if (empty($groupedItems[$tabSlug])): ?>
                    <p class="faq-empty-state">В этом разделе скоро появятся вопросы.</p>
                <?php else: ?>
                    <ul class="faq-question-list" role="list">
                        <?php foreach ($groupedItems[$tabSlug] as $index => $item): ?>
                            <?php
                            $questionId = 'faq-question-' . $tabSlug . '-' . $index;
                            $answerId = 'faq-answer-' . $tabSlug . '-' . $index;
                            ?>
                            <li class="faq-question-item">
                                <h3>
                                    <button
                                        id="<?= $questionId ?>"
                                        class="faq-question-trigger"
                                        type="button"
                                        aria-expanded="false"
                                        aria-controls="<?= $answerId ?>"
                                        data-faq-question
                                    >
                                        <span class="faq-question-title"><?= $item->question() ?></span>
                                        <span class="faq-question-icon" aria-hidden="true"></span>
                                    </button>
                                </h3>
                                <div
                                    id="<?= $answerId ?>"
                                    class="faq-answer-panel"
                                    role="region"
                                    aria-labelledby="<?= $questionId ?>"
                                    hidden
                                >
                                    <div class="faq-answer-content">
                                        <?= $item->answer()->kt() ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </div>
        <?php endforeach ?>
    </div>

    <?php if (!empty($faqEntities)): ?>
        <script type="application/ld+json">
            <?= json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqEntities,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        </script>
    <?php endif ?>
</div>
