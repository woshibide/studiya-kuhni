<?php
$contactsPage = page('contacts');

$panelTitle = $contactsPage ? $contactsPage->panel_title()->value() : '';
$phone = $contactsPage ? trim((string)$contactsPage->phone()->value()) : '';
$phoneDigits = preg_replace('/\D+/', '', $phone ?? '');
$phoneHref = $phoneDigits !== '' ? '+' . $phoneDigits : '';

$callbackTitle = $contactsPage ? $contactsPage->callback_title()->value() : '';

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
        return relative_url('assets/icons/tlg.svg');
    }

    if (
        str_contains($label, 'whatsapp') ||
        str_contains($label, 'wa.me') ||
        str_contains($label, 'ватсап')
    ) {
        return relative_url('assets/icons/wa.svg');
    }

    if (
        str_contains($label, 'yandex') ||
        str_contains($label, 'яндекс') ||
        str_contains($label, 'ya.ru')
    ) {
        return relative_url('assets/icons/ya.svg');
    }

    if (
        str_contains($label, '2gis') ||
        str_contains($label, '2гис')
    ) {
        return relative_url('assets/icons/2gis.svg');
    }

    return null;
};
?>

<aside class="nav-contact-panel" id="nav-contact-panel" aria-hidden="true" hidden>
    <div class="nav-contact-panel__content">
        <section class="nav-contact-section nav-contact-overview" aria-labelledby="nav-contact-studio-label">
            <div class="nav-contact-overview__main" style="--contact-stagger-index: 1">
                <h2 class="nav-contact-label" id="nav-contact-studio-label"><?= esc($studioName !== '' ? $studioName : ($panelTitle !== '' ? $panelTitle : 'Студия Кухни')) ?></h2>
                <?php if ($phone !== '' && $phoneHref !== ''): ?>
                    <a class="hover-underline nav-contact-link nav-contact-phone" href="tel:<?= esc($phoneHref, 'attr') ?>"><?= esc($phone) ?></a>
                <?php endif ?>
                <?php if ($studioEmail !== ''): ?>
                    <a class="hover-underline nav-contact-link nav-contact-email" href="mailto:<?= esc($studioEmail, 'attr') ?>"><?= esc($studioEmail) ?></a>
                <?php endif ?>

                <?php if (!empty($messengers)): ?>
                    <div class="nav-contact-messengers">
                        <?php foreach ($messengers as $messenger): ?>
                            <?php
                            $messengerTitle = (string)$messenger->title();
                            $messengerUrl = (string)$messenger->url();
                            $messengerIcon = $resolveContactIcon($messengerTitle, $messengerUrl);
                            ?>
                            <a class="hover-underline nav-contact-link" href="<?= esc($messengerUrl) ?>" target="_blank" rel="noopener noreferrer">
                                <?php if ($messengerIcon): ?>
                                    <img src="<?= esc($messengerIcon, 'attr') ?>" alt="" width="24" height="24" aria-hidden="true" loading="lazy" decoding="async">
                                <?php endif ?>
                                <span><?= esc($messengerTitle) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif ?>
            </div>
            <div class="nav-contact-location" style="--contact-stagger-index: 2">
                <h3 class="nav-contact-label">В салоне</h3>
                <?php if (trim((string)$address) !== ''): ?>
                    <div class="nav-contact-address-wrap">
                        <address>
                            <button class="nav-contact-address" type="button" data-copy-address="<?= esc(trim((string)$address), 'attr') ?>" aria-label="<?= esc('Скопировать адрес: ' . $address, 'attr') ?>" title="Скопировать адрес"><?= esc($address) ?></button>
                        </address>
                        <span class="nav-contact-copy-status sr-only" data-address-copy-status role="status" aria-live="polite"></span>
                    </div>
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
                            <a class="hover-underline nav-contact-link" href="<?= esc($mapUrl) ?>" target="_blank" rel="noopener noreferrer">
                                <?php if ($mapIcon): ?>
                                    <img src="<?= esc($mapIcon, 'attr') ?>" alt="" width="24" height="24" aria-hidden="true" loading="lazy" decoding="async">
                                <?php endif ?>
                                <span><?= esc($mapTitle) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif ?>
            </div>

        </section>

        <section class="nav-contact-section nav-contact-callback" aria-labelledby="nav-contact-callback-label">
            <h2 class="nav-contact-title" id="nav-contact-callback-label" style="--contact-stagger-index: 3"><?= esc($callbackTitle !== '' ? $callbackTitle : 'Заказать звонок') ?></h2>
            <?php snippet('callback-form', ['formId' => 'nav-callback-form', 'variant' => 'panel']) ?>
        </section>
    </div>
</aside>
