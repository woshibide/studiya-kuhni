<?php

namespace Studio;

use Kirby\Cms\App;
use Kirby\Cms\Page;

final class Panel
{
    public static function environment(App $kirby): array
    {
        $name = $kirby->option('studio.environment', 'local');
        $host = strtolower((string)$kirby->request()->url()->host());
        if ($name === 'staging' || str_starts_with($host, 'design.')) {
            return [
                'label' => 'Дизайн-версия',
                'theme' => 'notice',
                'text' => 'Рабочая версия для проверки. Изменения сохраняются здесь. Перенос на основной домен пока не настроен.',
            ];
        }
        if (Environment::indexable($name, $kirby->option('studio.productionUrl', ''), $kirby->request()->url()->toString())) {
            return [
                'label' => 'Основной сайт',
                'theme' => 'positive',
                'text' => 'Это основной сайт. Сохранение изменений опубликованной страницы обновляет её для посетителей. Подготовку и проверку новых материалов выполняйте в рабочей версии.',
            ];
        }
        return [
            'label' => $name === 'local' ? 'Локальная версия' : 'Рабочая версия',
            'theme' => 'info',
            'text' => 'Изменения сохраняются в этой рабочей копии. Публикация на основном домене отсюда не выполняется.',
        ];
    }

    public static function menu(App $kirby): array
    {
        $menu = [];
        $shortcutCurrent = false;
        foreach (['fabrics' => 'Фабрики и кухни', 'contacts' => 'Контакты и форма'] as $id => $label) {
            if ($page = self::editablePage($kirby, $id)) {
                $current = self::pagePathIsCurrent($kirby, $id);
                $shortcutCurrent = $shortcutCurrent || $current;
                $menu['studio-' . $id] = [
                    'label' => $label,
                    'icon' => $id === 'fabrics' ? 'grid' : 'phone',
                    'link' => $page->panel()->url(true),
                    'current' => static fn (?string $area): bool => $area === 'site' && $current,
                ];
            }
        }
        $menu['studio-guide'] = ['label' => 'Помощь', 'icon' => 'question'];
        return [
            'site' => [
                'label' => 'Страницы',
                'icon' => 'page',
                'current' => static fn (?string $area): bool => $area === 'site' && !$shortcutCurrent,
            ],
            ...$menu, '-', 'languages', 'users', 'system',
        ];
    }

    private static function pagePathIsCurrent(App $kirby, string $id): bool
    {
        $panelRoot = trim((string)parse_url(\Kirby\Panel\Panel::url(), PHP_URL_PATH), '/');
        $path = trim(rawurldecode($kirby->request()->url()->path()->toString()), '/');
        if (!str_starts_with($path, $panelRoot . '/')) return false;

        $path = substr($path, strlen($panelRoot) + 1);
        $prefix = 'pages/' . str_replace('/', '+', $id);
        return $path === $prefix || str_starts_with($path, $prefix . '+') || str_starts_with($path, $prefix . '/');
    }

    public static function pageButtons(): array
    {
        return [
            'studio-environment' => static fn (App $kirby) => self::environmentButton($kirby),
            'studio-parent' => static function (Page $page): ?array {
                $parent = $page->parent();
                if ($page->intendedTemplate()->name() !== 'kuhnya' || !$parent || !$parent->isAccessible()) return null;
                return ['icon' => 'angle-left', 'text' => 'Фабрика', 'title' => 'Редактировать фабрику: ' . $parent->title()->value(), 'link' => $parent->panel()->url(true)];
            },
            'open', 'preview', '-', 'settings', 'languages', 'status',
        ];
    }

    public static function siteButtons(): array
    {
        return [
            'studio-environment' => static fn (App $kirby) => self::environmentButton($kirby),
            'open', 'preview', 'languages',
        ];
    }

    private static function environmentButton(App $kirby): array
    {
        $environment = self::environment($kirby);
        return [
            'icon' => 'info',
            'text' => $environment['label'],
            'title' => $environment['text'],
            'theme' => $environment['theme'],
            'link' => '/studio-guide',
            'responsive' => false,
        ];
    }

    private static function editablePage(App $kirby, string $id): ?Page
    {
        $page = $kirby->page($id);
        return $page?->isAccessible() ? $page : null;
    }

    public static function guide(App $kirby): array
    {
        $links = [['text' => 'Все страницы', 'icon' => 'page', 'link' => '/site']];
        foreach (['home' => 'Главная', 'fabrics' => 'Фабрики и кухни', 'contacts' => 'Контакты и форма', 'faq' => 'Вопросы и ответы'] as $id => $label) {
            if ($page = self::editablePage($kirby, $id)) {
                $links[] = ['text' => $label, 'icon' => 'angle-right', 'link' => $page->panel()->url(true)];
            }
        }
        $archiveExcluded = in_array('archive', $kirby->option('studio.unpublishedPaths', []), true);
        $sections = [
            [
                'title' => 'Изменить и проверить страницу',
                'text' => '<ol><li>Откройте страницу и выберите нужную вкладку. Подсказки под полями объясняют, где появится содержимое.</li><li>Внесите изменения. Пока они не сохранены кнопкой «Сохранить», это рабочие изменения, а не новая версия страницы для посетителей.</li><li>Откройте «Предпросмотр», чтобы проверить рабочие изменения. В предпросмотре можно сравнить их с сохранённой версией.</li><li>Сохраните проверенные изменения. Если материал ещё не готов, оставьте страницу в статусе «Черновик».</li></ol>',
            ],
            [
                'title' => 'Сохранение и статус - разные действия',
                'text' => '<p>«Сохранить» обновляет содержимое страницы в текущей версии сайта. Статус определяет доступность: черновик скрыт от посетителей; опубликованная страница доступна, а сортировка задаёт порядок в разделе.</p><p>Пункт «Изменения» в меню собирает незавершённую работу. Отмена изменений возвращает последнюю сохранённую версию. Если страницу редактирует другой человек, не перехватывайте её без согласования.</p><p>Черновики проверяйте через штатный предпросмотр в рабочей или дизайн-версии, войдя в Панель. На основном сайте черновики и исключённые из запуска разделы закрыты даже для такого предпросмотра.</p>',
            ],
            [
                'title' => 'После обновления Панели',
                'text' => '<p>Если после обновления Панели редактор не отображается, появляется ошибка поля или открывается пустая страница, сначала сохраните нужные изменения в открытых вкладках. Если сохранить не удаётся, скопируйте несохранённый текст в отдельный документ.</p><p>Затем обновите страницу браузера. Переключение вкладок внутри Панели не перезагружает редактор.</p>',
            ],
            [
                'title' => 'Фабрика и её кухни',
                'text' => '<p>Кухни находятся внутри своей фабрики. В карточке кухни редактируются её название, описание, цена, характеристики и фотографии.</p><p>Логотип, описание и карта фабрики общие для всех её кухонь. Откройте кнопку «Фабрика» над карточкой кухни, чтобы изменить источник этих данных. Удаление точки карты скрывает карту фабрики и её кухонь.</p><p>Общие контакты и подписи формы редактируются на странице «Контакты». Настройки поиска по умолчанию находятся в разделе сайта «Настройки сайта».</p>',
            ],
            [
                'title' => 'Текст, ссылки и изображения',
                'text' => '<p>В редакторе Tiptap выделите текст и выберите оформление на панели инструментов. Доступные кнопки зависят от поля. Для ссылок на материалы сайта выбирайте страницу или файл; фотографии добавляйте через предусмотренные поля или доступную кнопку файла.</p><p>У изображений задавайте содержательное описание, если они передают информацию. Проверяйте ссылки и переносы текста в предпросмотре, особенно на узком экране.</p><p>Клавиатура: Tab переключает поля и панель инструментов; стрелки влево и вправо выбирают её кнопки, Home и End переходят к первой и последней. Ctrl+B / ⌘B - жирный текст, Ctrl+I / ⌘I - курсив, Ctrl+K / ⌘K - ссылка, если эти действия доступны в поле.</p>',
            ],
            [
                'title' => 'Запуск и перенос на основной домен',
                'text' => ($archiveExcluded ? '<p>Архив и его публикации пока не входят в текущий запуск, даже со статусом «Опубликована». Менять это ограничение через статус страницы нельзя.</p>' : '') . '<p>Кнопка переноса проверенной дизайн-версии на основной домен пока не настроена. Сохранение страницы и смена её статуса не выполняют такой перенос.</p>',
            ],
        ];

        return [
            'component' => 'k-studio-guide-view',
            'title' => 'Как редактировать сайт',
            'props' => [
                'title' => 'Как редактировать сайт',
                'environment' => self::environment($kirby),
                'links' => $links,
                'sections' => $sections,
            ],
        ];
    }

    public static function translations(): array
    {
        return [
            'tiptap.toolbar.button.horizontalRule' => 'Разделитель',
            'tiptap.toolbar.button.codeBlock' => 'Блок кода',
            'tiptap.toolbar.button.blockquote' => 'Цитата',
            'tiptap.toolbar.button.taskList' => 'Список задач',
            'tiptap.upload.error.disabled' => 'Загрузка файлов недоступна в этом поле.',
            'tiptap.upload.error.noData' => 'Файл не получен. Выберите его ещё раз.',
            'tiptap.upload.error.insert' => 'Не удалось вставить загруженный файл. Выберите его из файлов страницы.',
            'tiptap.upload.error.dialog' => 'Не удалось открыть окно загрузки. Обновите страницу и попробуйте снова.',
            'tiptap.upload.error.failed' => 'Не удалось загрузить файл. Проверьте соединение и попробуйте снова.',
            'tiptap.navigate.error' => 'Не удалось открыть связанный материал.',
            'locator.placeholder' => 'Найдите адрес или введите координаты',
            'locator.locate' => 'Найти',
            'locator.collapse' => 'Свернуть',
            'locator.latitude' => 'Широта',
            'locator.longitude' => 'Долгота',
            'locator.number' => 'Номер дома',
            'locator.address' => 'Адрес',
            'locator.postcode' => 'Почтовый индекс',
            'locator.city' => 'Город',
            'locator.region' => 'Регион',
            'locator.country' => 'Страна',
            'locator.countryCode' => 'Код страны',
            'locator.osm' => 'Идентификатор OpenStreetMap',
            'locator.empty' => 'Местоположение пока не указано.',
            'locator.empty_response' => 'Место не найдено. Уточните адрес или введите координаты.',
            'locator.error' => 'Не удалось найти место. Попробуйте снова или обратитесь к администратору.',
            'locator.reset' => 'Удалить точку',
        ];
    }
}
