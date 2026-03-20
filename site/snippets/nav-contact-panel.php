<?php
$contactsPage = page('contacts');

$panelTitle = $contactsPage ? $contactsPage->panel_title()->value() : '';
$panelCloseText = $contactsPage ? $contactsPage->panel_close_text()->value() : '';
$panelCloseAriaLabel = $contactsPage ? $contactsPage->panel_close_aria_label()->value() : '';

$helpTitle = $contactsPage ? $contactsPage->help_title()->value() : '';
$phone = $contactsPage ? trim((string)$contactsPage->phone()->value()) : '';
$phoneDigits = preg_replace('/\D+/', '', $phone ?? '');
$phoneHref = $phoneDigits !== '' ? '+' . $phoneDigits : '';

$callbackTitle = $contactsPage ? $contactsPage->callback_title()->value() : '';
$phonePlaceholder = $contactsPage ? $contactsPage->callback_phone_placeholder()->value() : '';
$namePlaceholder = $contactsPage ? $contactsPage->callback_name_placeholder()->value() : '';
$emailPlaceholder = $contactsPage ? $contactsPage->callback_email_placeholder()->value() : '';
$consentText = $contactsPage ? $contactsPage->consent_text()->value() : '';
$callbackButton = $contactsPage ? $contactsPage->callback_button_text()->value() : '';

$studioName = $contactsPage ? $contactsPage->studio_name()->value() : '';
$studioEmail = $contactsPage ? trim((string)$contactsPage->email()->value()) : '';
$address = $contactsPage ? $contactsPage->address()->value() : '';
$hours = $contactsPage ? $contactsPage->hours()->value() : '';

$messengers = $contactsPage && $contactsPage->messengers()->isNotEmpty() ? $contactsPage->messengers()->toStructure() : [];
$mapLinks = $contactsPage && $contactsPage->map_links()->isNotEmpty() ? $contactsPage->map_links()->toStructure() : [];
?>

<aside class="nav-contact-panel" id="nav-contact-panel" aria-hidden="true" hidden>
    <div class="nav-contact-panel__header">
        <h2><?= esc($panelTitle) ?></h2>
        <button class="nav-contact-panel__close hover-underline" type="button" aria-label="<?= esc($panelCloseAriaLabel, 'attr') ?>">
            <?= esc($panelCloseText) ?>
        </button>
    </div>

    <div class="nav-contact-panel__content">
        <section class="nav-contact-section nav-contact-help" aria-label="Основные контакты">
            <p class="nav-contact-title"><?= esc($helpTitle) ?></p>
            <?php if ($phone !== '' && $phoneHref !== ''): ?>
                <a href="tel:<?= esc($phoneHref) ?>" class="nav-contact-phone"><?= esc($phone) ?></a>
            <?php endif ?>

            <?php if (!empty($messengers)): ?>
                <div class="nav-contact-messengers">
                    <?php foreach ($messengers as $messenger): ?>
                        <a href="<?= esc($messenger->url()) ?>" target="_blank" rel="noopener noreferrer">
                            <?= esc($messenger->title()) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif ?>
        </section>

        <section class="nav-contact-section nav-contact-callback" aria-label="Форма обратной связи">
            <h3 class="nav-contact-title"><?= esc($callbackTitle) ?></h3>
            <form class="nav-contact-form" method="post" action="#">
                <input type="tel" name="telephone" placeholder="<?= esc($phonePlaceholder) ?>" required>
                <input type="text" name="name" placeholder="<?= esc($namePlaceholder) ?>" required>
                <input type="email" name="email" placeholder="<?= esc($emailPlaceholder) ?>">
                <label class="nav-contact-consent">
                    <input type="checkbox" name="consent" required>
                    <span><?= esc($consentText) ?></span>
                </label>
                <button class="primary-btn" type="submit"><?= esc($callbackButton) ?></button>
            </form>
        </section>

        <section class="nav-contact-section nav-contact-primary" aria-label="Шоурум и график">
            <div class="nav-contact-primary__header">
                <h3><?= esc($studioName) ?></h3>
                <?php if ($studioEmail !== ''): ?>
                    <a href="mailto:<?= esc($studioEmail) ?>"><?= esc($studioEmail) ?></a>
                <?php endif ?>
            </div>
            <?php if (trim((string)$address) !== ''): ?>
                <address><?= esc($address) ?></address>
            <?php endif ?>

            <?php if (!empty($mapLinks)): ?>
                <div class="nav-contact-maps">
                    <?php foreach ($mapLinks as $mapLink): ?>
                        <a href="<?= esc($mapLink->url()) ?>" target="_blank" rel="noopener noreferrer">
                            <?= esc($mapLink->title()) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif ?>

            <?php if (trim((string)$hours) !== ''): ?>
                <p class="nav-contact-hours"><?= esc($hours) ?></p>
            <?php endif ?>
        </section>
    </div>
</aside>
