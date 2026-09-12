<?php
use Studio\Callback\Submission;

$formId = $formId ?? 'callback-form';
$variant = $variant ?? 'page';
$contacts = page('contacts');
$available = Submission::available(studio_callback_config());
$flash = null;
if ($variant === 'page') {
    $flash = $kirby->session()->get('studio.callback.flash');
    $kirby->session()->remove('studio.callback.flash');
    if (!is_array($flash) || ($flash['expires'] ?? 0) < time()) {
        $flash = null;
    }
}
$result = $flash['result'] ?? null;
$values = $flash['values'] ?? [];
$errors = $result['errors'] ?? [];
$source = $values['source'] ?? $page->id();
$fields = [
    'telephone' => ['label' => 'Телефон', 'type' => 'tel', 'autocomplete' => 'tel', 'maxlength' => 50, 'placeholder' => $contacts?->callback_phone_placeholder()->value() ?? ''],
    'name' => ['label' => 'Имя', 'type' => 'text', 'autocomplete' => 'name', 'maxlength' => 100, 'placeholder' => $contacts?->callback_name_placeholder()->value() ?? ''],
    'email' => ['label' => 'Электронная почта', 'type' => 'email', 'autocomplete' => 'email', 'maxlength' => 254, 'placeholder' => $contacts?->callback_email_placeholder()->value() ?? ''],
];
$phone = trim((string)$contacts?->phone()->value());
$phoneDigits = preg_replace('/\D+/', '', $phone);
$consentText = (string)($contacts?->consent_text()->or('Согласен на обработку персональных данных')->value() ?? 'Согласен на обработку персональных данных');
$consentHtml = esc($consentText);
if ($privacy = page('privacy')) {
    $privacyLink = static fn (string $text): string => '<a class="callback-form__privacy hover-underline" href="' . esc($privacy->url(), 'attr') . '" target="_blank" rel="noopener">' . esc($text) . '</a>';
    if (preg_match('/порядком обработки (?:персональных )?данных|персональных данных/ui', $consentText, $match, PREG_OFFSET_CAPTURE)) {
        [$phrase, $offset] = $match[0];
        $consentHtml = esc(substr($consentText, 0, $offset)) . $privacyLink($phrase) . esc(substr($consentText, $offset + strlen($phrase)));
    } else {
        $consentHtml .= ' (' . $privacyLink('политика обработки данных') . ')';
    }
}
?>
<form class="callback-form callback-form--<?= esc($variant, 'attr') ?>" id="<?= esc($formId, 'attr') ?>" action="<?= esc(url('callback'), 'attr') ?>" method="post" data-callback-form>
    <div class="callback-form__status" data-callback-status role="status" tabindex="-1"<?= $result === null ? ' hidden' : '' ?>>
        <?php if ($result): ?>
            <p><?= esc($result['message']) ?></p>
            <?php if ($errors): ?>
                <ul>
                    <?php foreach ($errors as $field => $error): ?>
                        <li><a href="#<?= esc($formId . '-' . $field, 'attr') ?>"><?= esc($error) ?></a></li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        <?php endif ?>
    </div>
    <?php if (!$available): ?>
        <p class="callback-form__notice" id="<?= esc($formId, 'attr') ?>-availability">
            <?= esc(Submission::UNAVAILABLE) ?>
            <?php if ($phoneDigits !== ''): ?>
                <a href="tel:+<?= esc($phoneDigits, 'attr') ?>"><?= esc($phone) ?></a>
            <?php endif ?>
        </p>
    <?php endif ?>
    <input type="hidden" name="csrf" value="<?= esc(csrf(), 'attr') ?>">
    <input type="hidden" name="source" value="<?= esc($source, 'attr') ?>">
    <div class="callback-form__trap" aria-hidden="true" inert>
        <label for="<?= esc($formId, 'attr') ?>-website">Оставьте это поле пустым</label>
        <input type="text" id="<?= esc($formId, 'attr') ?>-website" name="website" tabindex="-1" autocomplete="off">
    </div>
    <?php foreach ($fields as $field => $settings): ?>
        <div class="callback-form__field callback-form__field--<?= esc($field, 'attr') ?>">
            <label for="<?= esc($formId . '-' . $field, 'attr') ?>"><?= esc($settings['label']) ?></label>
            <input
                id="<?= esc($formId . '-' . $field, 'attr') ?>"
                type="<?= esc($settings['type'], 'attr') ?>"
                name="<?= esc($field, 'attr') ?>"
                autocomplete="<?= esc($settings['autocomplete'], 'attr') ?>"
                maxlength="<?= $settings['maxlength'] ?>"
                placeholder="<?= esc($settings['placeholder'], 'attr') ?>"
                value="<?= esc($values[$field] ?? '', 'attr') ?>"
                aria-describedby="<?= esc($formId . '-' . $field . '-error', 'attr') ?>"
                <?= isset($errors[$field]) ? 'aria-invalid="true"' : '' ?>
                <?= $field !== 'email' ? 'required' : '' ?>
                <?= $field === 'name' ? 'minlength="2"' : '' ?>
                <?= $field === 'telephone' ? 'inputmode="tel"' : '' ?>
                <?= $field === 'email' ? 'spellcheck="false" autocapitalize="none"' : '' ?>
            >
            <p class="callback-form__error" id="<?= esc($formId . '-' . $field . '-error', 'attr') ?>" data-error-for="<?= esc($field, 'attr') ?>"<?= isset($errors[$field]) ? '' : ' hidden' ?>><?= esc($errors[$field] ?? '') ?></p>
        </div>
    <?php endforeach ?>
    <div class="callback-form__field callback-form__field--consent">
        <label class="callback-form__consent" for="<?= esc($formId, 'attr') ?>-consent">
            <input type="checkbox" id="<?= esc($formId, 'attr') ?>-consent" name="consent" value="1" required aria-describedby="<?= esc($formId, 'attr') ?>-consent-error"<?= ($values['consent'] ?? '') === '1' ? ' checked' : '' ?><?= isset($errors['consent']) ? ' aria-invalid="true"' : '' ?>>
            <span><?= $consentHtml ?></span>
        </label>
        <p class="callback-form__error" id="<?= esc($formId, 'attr') ?>-consent-error" data-error-for="consent"<?= isset($errors['consent']) ? '' : ' hidden' ?>><?= esc($errors['consent'] ?? '') ?></p>
    </div>
    <button class="primary-btn" type="submit"<?= !$available ? ' disabled aria-describedby="' . esc($formId, 'attr') . '-availability"' : '' ?>><?= esc($contacts?->callback_button_text()->or('Заказать звонок')->value() ?? 'Заказать звонок') ?></button>
</form>
