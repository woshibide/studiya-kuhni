<?php
$contactsPage = page('contacts');

$panelTitle = $contactsPage ? $contactsPage->panel_title()->value() : '';
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

$resolveContactIcon = static function (string $title, string $url): ?string {
    $label = function_exists('mb_strtolower') ? mb_strtolower($title . ' ' . $url) : strtolower($title . ' ' . $url);

    if (
        str_contains($label, 'telegram') ||
        str_contains($label, 't.me') ||
        str_contains($label, 'телеграм')
    ) {
        return '/assets/icons/tlg.svg';
    }

    if (
        str_contains($label, 'whatsapp') ||
        str_contains($label, 'wa.me') ||
        str_contains($label, 'ватсап')
    ) {
        return '/assets/icons/wa.svg';
    }

    if (
        str_contains($label, 'yandex') ||
        str_contains($label, 'яндекс') ||
        str_contains($label, 'ya.ru')
    ) {
        return '/assets/icons/ya.svg';
    }

    if (
        str_contains($label, '2gis') ||
        str_contains($label, '2гис')
    ) {
        return '/assets/icons/2gis.svg';
    }

    return null;
};
?>

<aside class="nav-contact-panel" id="nav-contact-panel" aria-hidden="true" hidden>
    <div class="nav-contact-panel__content">
        <section class="nav-contact-section nav-contact-overview" aria-label="Контакты студии">
            <div class="nav-contact-overview__main">
                <h2><?= esc($studioName !== '' ? $studioName : $panelTitle) ?></h2>
                <?php if ($studioEmail !== ''): ?>
                    <a href="mailto:<?= esc($studioEmail) ?>"><?= esc($studioEmail) ?></a>
                <?php endif ?>
                <?php if ($phone !== '' && $phoneHref !== ''): ?>
                    <a href="tel:<?= esc($phoneHref) ?>" class="nav-contact-phone"><?= esc($phone) ?></a>
                <?php endif ?>

                <?php if (!empty($messengers)): ?>
                    <div class="nav-contact-messengers">
                        <?php foreach ($messengers as $messenger): ?>
                            <?php
                            $messengerTitle = (string)$messenger->title();
                            $messengerUrl = (string)$messenger->url();
                            $messengerIcon = $resolveContactIcon($messengerTitle, $messengerUrl);
                            ?>
                            <a class="nav-contact-link-with-icon" href="<?= esc($messengerUrl) ?>" target="_blank" rel="noopener noreferrer">
                                <?php if ($messengerIcon): ?>
                                    <img src="<?= esc($messengerIcon, 'attr') ?>" alt="" aria-hidden="true" loading="lazy">
                                <?php endif ?>
                                <span><?= esc($messengerTitle) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif ?>
            </div>
            <div>


                <?php if (trim((string)$address) !== ''): ?>
                    <address><?= esc($address) ?></address>
                    <?php if (trim((string)$hours) !== ''): ?>
                        <p class="nav-contact-hours"><?= esc($hours) ?></p>
                    <?php endif ?>
                <?php endif ?>

                <?php if (!empty($mapLinks)): ?>
                    <div class="nav-contact-maps">
                        <?php foreach ($mapLinks as $mapLink): ?>
                            <?php
                            $mapTitle = (string)$mapLink->title();
                            $mapUrl = (string)$mapLink->url();
                            $mapIcon = $resolveContactIcon($mapTitle, $mapUrl);
                            ?>
                            <a class="nav-contact-link-with-icon" href="<?= esc($mapUrl) ?>" target="_blank" rel="noopener noreferrer">
                                <?php if ($mapIcon): ?>
                                    <img src="<?= esc($mapIcon, 'attr') ?>" alt="" aria-hidden="true" loading="lazy">
                                <?php endif ?>
                                <span><?= esc($mapTitle) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif ?>
            </div>

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
    </div>
</aside>