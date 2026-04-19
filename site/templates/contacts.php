<?php snippet('header') ?>

<?php
$phone = trim((string)$page->phone()->value());
$phoneDigits = preg_replace('/\D+/', '', $phone ?? '');
$phoneHref = $phoneDigits !== '' ? '+' . $phoneDigits : '';

$introText = $page->intro_text()->value();
$contactsTitle = $page->help_title()->value();
$callbackTitle = $page->callback_title()->value();
$addressTitle = $page->address_section_title()->value();
$phonePlaceholder = $page->callback_phone_placeholder()->value();
$namePlaceholder = $page->callback_name_placeholder()->value();
$emailPlaceholder = $page->callback_email_placeholder()->value();
$consentText = $page->consent_text()->value();
$callbackButton = $page->callback_button_text()->value();

$studioName = $page->studio_name()->value();
$studioEmail = trim((string)$page->email()->value());
$address = $page->address()->value();
$hours = $page->hours()->value();

$messengers = $page->messengers()->isNotEmpty() ? $page->messengers()->toStructure() : [];
$mapLinks = $page->map_links()->isNotEmpty() ? $page->map_links()->toStructure() : [];

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

<main class="contacts-page">

    <section>
        <h1>
            Контакты
        </h1>
    </section>


    <section class="section-wrapper" id="contacts">
        <div class="contacts-layout">
            <div class="contacts-form-column">
                <div class="contacts-block contacts-block--form" aria-label="<?= esc($callbackTitle, 'attr') ?>">
                    <h2><?= esc($callbackTitle) ?></h2>
                    <form class="contacts-form" method="post" action="#">
                        <input type="tel" name="telephone" placeholder="<?= esc($phonePlaceholder) ?>" required>
                        <input type="text" name="name" placeholder="<?= esc($namePlaceholder) ?>" required>
                        <input type="email" name="email" placeholder="<?= esc($emailPlaceholder) ?>">

                        <label class="contacts-consent">
                            <input type="checkbox" name="consent" required>
                            <span><?= esc($consentText) ?></span>
                        </label>

                        <button class="primary-btn" type="submit"><?= esc($callbackButton) ?></button>
                    </form>
                </div>
            </div>

            <div class="contacts-content">

                <div class="contacts-block contacts-block--address" aria-label="<?= esc($addressTitle, 'attr') ?>">

                    <?php if (trim((string)$studioName) !== ''): ?>
                        <p class="contacts-studio-name"><?= esc($studioName) ?></p>
                    <?php endif ?>

                    <?php if (trim((string)$address) !== ''): ?>
                        <address><?= esc($address) ?></address>
                    <?php endif ?>

                    <?php if (!empty($mapLinks)): ?>
                        <div class="contacts-links" aria-label="Карты">
                            <?php foreach ($mapLinks as $mapLink): ?>
                                <?php
                                $mapTitle = (string)$mapLink->title();
                                $mapUrl = (string)$mapLink->url();
                                $mapIcon = $resolveContactIcon($mapTitle, $mapUrl);
                                ?>
                                <a class="hover-underline external-link__hidden contacts-link-with-icon" href="<?= esc($mapUrl) ?>" target="_blank" rel="noopener noreferrer">
                                    <?php if ($mapIcon): ?>
                                        <img src="<?= esc($mapIcon, 'attr') ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                    <?php endif ?>
                                    <span><?= esc($mapTitle) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif ?>

                    <?php if (trim((string)$hours) !== ''): ?>
                        <p class="contacts-hours"><?= esc($hours) ?></p>
                    <?php endif ?>
                </div>

                <div class="contacts-block contacts-block--contacts" aria-label="<?= esc($contactsTitle, 'attr') ?>">
                    <?php if ($studioEmail !== ''): ?>
                        <a href="mailto:<?= esc($studioEmail) ?>"><?= esc($studioEmail) ?></a>
                    <?php endif ?>


                    <?php if ($phone !== '' && $phoneHref !== ''): ?>
                        <a class="contacts-phone" href="tel:<?= esc($phoneHref) ?>"><?= esc($phone) ?></a>
                    <?php endif ?>
                    
                    <?php if (!empty($messengers)): ?>
                        <div class="contacts-links" aria-label="Мессенджеры">
                            <?php foreach ($messengers as $messenger): ?>
                                <?php
                                $messengerTitle = (string)$messenger->title();
                                $messengerUrl = (string)$messenger->url();
                                $messengerIcon = $resolveContactIcon($messengerTitle, $messengerUrl);
                                ?>
                                <a class="hover-underline contacts-link-with-icon" href="<?= esc($messengerUrl) ?>" target="_blank" rel="noopener noreferrer">
                                    <?php if ($messengerIcon): ?>
                                        <img src="<?= esc($messengerIcon, 'attr') ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                    <?php endif ?>
                                    <span><?= esc($messengerTitle) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif ?>
                </div>


            </div>
        </div>
    </section>
</main>

<?php snippet('footer') ?>
