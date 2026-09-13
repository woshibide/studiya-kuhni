<?php

namespace Studio\Callback;

use Kirby\Cms\App;
use Kirby\Exception\PermissionException;
use Kirby\Toolkit\Escape;

final class Inbox
{
    public static function allowed(App $kirby): bool
    {
        $user = $kirby->user();
        return $user !== null
            && $user->role()->permissions()->for('access', 'panel')
            && $user->role()->permissions()->for('access', 'studio-callback')
            && $user->role()->permissions()->for('studio.callback', 'manage');
    }

    public static function authorize(App $kirby): void
    {
        if (!self::allowed($kirby)) throw new PermissionException(message: 'Нет доступа к заявкам.');
    }

    public static function store(App $kirby): Store
    {
        self::authorize($kirby);
        return Store::for($kirby);
    }

    public static function authorizeWrite(App $kirby): void
    {
        self::authorize($kirby);
        if ($kirby->auth()->csrf() === false) throw new PermissionException(message: 'Сессия устарела. Обновите страницу.');
    }

    public static function area(App $kirby): array
    {
        return [
            'label' => 'Заявки', 'icon' => 'phone', 'menu' => Inbox::allowed($kirby),
            'link' => 'studio-callback',
            'views' => [
                ['pattern' => 'studio-callback', 'action' => fn () => Inbox::listing($kirby)],
                ['pattern' => 'studio-callback/(:any)', 'action' => fn (string $id) => Inbox::detail($kirby, $id)],
            ],
            'dialogs' => [
                'callback.update' => [
                    'pattern' => 'studio-callback/(:any)/update',
                    'load' => fn (string $id) => Inbox::edit($kirby, $id),
                    'submit' => function (string $id) use ($kirby) {
                        Inbox::authorizeWrite($kirby);
                        Inbox::store($kirby)->update($id, $kirby->request()->body()->toArray(), $kirby->user()->id());
                        return ['event' => 'studio.callback.update'];
                    },
                ],
                'callback.delete' => [
                    'pattern' => 'studio-callback/(:any)/delete/(:num)',
                    'load' => function (string $id, string $revision) use ($kirby) {
                        Inbox::store($kirby)->find($id);
                        return ['component' => 'k-remove-dialog', 'props' => ['text' => 'Удалить заявку и заметку без возможности восстановления?']];
                    },
                    'submit' => function (string $id, string $revision) use ($kirby) {
                        Inbox::authorizeWrite($kirby);
                        Inbox::store($kirby)->delete($id, (int)$revision);
                        return ['event' => 'studio.callback.delete', 'redirect' => '/studio-callback'];
                    },
                ],
            ],
        ];
    }

    public static function listing(App $kirby): array
    {
        self::authorize($kirby);
        $props = ['configured' => Store::directory($kirby) !== '', 'items' => [], 'filters' => [], 'pagination' => []];
        if ($props['configured']) {
            $status = $kirby->request()->get('status', 'new');
            $status = is_string($status) ? $status : 'new';
            $data = self::store($kirby)->listing($status, (int)$kirby->request()->get('page', 1));
            $props['pagination'] = $data['pagination'];
            foreach (Store::STATUSES + ['all' => 'Все'] as $key => $label) {
                $count = $key === 'all' ? array_sum($data['counts']) : $data['counts'][$key];
                $props['filters'][] = ['name' => $key, 'label' => $label . ' · ' . $count, 'link' => '/studio-callback?status=' . $key];
            }
            $props['items'] = array_map(static fn (array $row) => [
                'id' => $row['id'], 'text' => Escape::html($row['name']),
                'info' => Escape::html($row['telephone'] . ' · ' . self::date($row['created']) . ' · ' . Store::STATUSES[$row['status']]),
                'link' => '/studio-callback/' . $row['id'],
                'image' => ['icon' => 'phone', 'back' => 'white'],
            ], $data['records']);
            $props['status'] = $status;
        }
        return ['component' => 'k-studio-callback-inbox', 'title' => 'Заявки', 'props' => $props];
    }

    public static function detail(App $kirby, string $id): array
    {
        $row = self::store($kirby)->find($id);
        $fields = [];
        $labels = [
            'name' => 'Имя', 'telephone' => 'Телефон', 'email' => 'Электронная почта',
            'created' => 'Получена (UTC)', 'source_title' => 'Страница', 'source_url' => 'Адрес страницы',
            'consent' => 'Согласие на обработку данных', 'status' => 'Статус',
            'email_status' => 'Уведомление по почте', 'updated' => 'Последнее изменение (UTC)', 'notes' => 'Заметка',
        ];
        foreach ($labels as $name => $label) {
            $fields[$name] = ['name' => $name, 'label' => $label, 'type' => $name === 'notes' ? 'textarea' : 'text', 'disabled' => true, 'width' => in_array($name, ['source_url', 'notes'], true) ? '1/1' : '1/2'];
        }
        $values = array_intersect_key($row, $labels);
        $values['created'] = self::date($row['created']);
        $values['updated'] = self::date($row['updated']);
        $values['status'] = Store::STATUSES[$row['status']];
        $values['consent'] = 'Получено при отправке';
        $values['email_status'] = ['pending' => 'Доставка не подтверждена', 'sent' => 'Отправлено', 'failed' => 'Не отправлено. Заявка сохранена.'][$row['email_status']];
        return [
            'component' => 'k-studio-callback-detail', 'title' => 'Заявка',
            'breadcrumb' => [['label' => 'Заявка', 'link' => '/studio-callback/' . $id]],
            'props' => ['id' => $id, 'revision' => (int)$row['revision'], 'fields' => $fields, 'values' => $values],
        ];
    }

    public static function edit(App $kirby, string $id): array
    {
        $row = self::store($kirby)->find($id);
        $options = [];
        foreach (Store::STATUSES as $value => $text) $options[] = compact('value', 'text');
        return [
            'component' => 'k-form-dialog',
            'props' => [
                'fields' => [
                    'status' => ['label' => 'Статус', 'type' => 'select', 'required' => true, 'empty' => false, 'options' => $options],
                    'notes' => ['label' => 'Заметка', 'type' => 'textarea', 'buttons' => false, 'maxlength' => 5000, 'help' => 'Результат звонка и следующий шаг. До 5000 символов.'],
                ],
                'value' => ['status' => $row['status'], 'notes' => $row['notes'], 'revision' => (int)$row['revision']],
                'submitButton' => 'Сохранить',
            ],
        ];
    }

    private static function date(string $value): string
    {
        return gmdate('d.m.Y H:i', strtotime($value));
    }
}
