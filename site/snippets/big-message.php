<?php
$messageText = trim((string)($text ?? $page->big_message()->value()));

if ($messageText === '') {
    $messageText = 'Кухни. Созданные для вашей жизни';
}

$tag = $tag ?? 'p';
$allowedTags = ['p', 'h2', 'h3'];

if (in_array($tag, $allowedTags, true) === false) {
    $tag = 'p';
}
?>
<div class="section-wrapper big-message" role="note">
    <<?= $tag ?> class="big-message__text"><?= esc($messageText) ?></<?= $tag ?>>
</div>
