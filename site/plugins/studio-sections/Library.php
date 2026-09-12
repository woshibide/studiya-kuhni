<?php

declare(strict_types=1);

namespace Studio\Sections;

use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Cms\Structure;
use Kirby\Content\Field;
use Kirby\Data\Data;
use Kirby\Exception\InvalidArgumentException;
use Studio\RichText;

final class Library
{
    public const FIELDS = [
        'benefits' => 'benefit_library',
        'kitchen_features' => 'kitchen_feature_library',
        'kitchen_benefits' => 'kitchen_benefit_library',
    ];

    public static function rows(mixed $value): array
    {
        $rows = Data::decode($value, 'yaml');
        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    public static function owner(Site $site): Page
    {
        return $site->find('fabrics') ?? throw new \RuntimeException('The fabrics page is required for the section library.');
    }

    public static function pool(Site $site, string $kind): array
    {
        $field = self::FIELDS[$kind];
        $owner = $site->find('fabrics');
        // Keep the first library format readable while migrating its location.
        $content = $owner?->version('latest')->content();
        $legacyField = $kind === 'kitchen_features' ? 'feature_library' : $field;
        $value = $content?->has($field) ? $content->get($field) : $site->version('latest')->content()->get($legacyField);
        $pool = [];
        foreach (self::rows($value->value()) as $row) {
            if (is_string($row['key'] ?? null) && $row['key'] !== '') $pool[$row['key']] = $row;
        }
        return $pool;
    }

    public static function availablePool(Page $page, string $kind): array
    {
        $pool = $kind === 'benefits' ? self::pool($page->site(), 'benefits') : [];
        if ($page->intendedTemplate()->name() === 'kuhnya') {
            $pool = self::pool($page->site(), 'kitchen_' . $kind) + $pool;
        }
        return $pool;
    }

    public static function hasText(string $value, object $parent): bool
    {
        $plain = RichText::plain(new Field($parent, 'text', $value));
        return preg_replace('/[\s\p{Z}\x{200B}\x{FEFF}]+/u', '', $plain) !== '';
    }

    public static function resolve(Page $page, string $kind, ?string $field = null): Structure
    {
        $name = $field ?? ($kind === 'features' ? 'kitchen_features' : 'benefits_items');
        $pool = self::availablePool($page, $kind);
        $resolved = [];
        $seen = [];
        foreach (self::rows($page->content()->get($name)->value()) as $row) {
            $reference = $row['reference'] ?? '';
            if ($reference !== '') {
                if (!is_string($reference) || isset($seen[$reference]) || !isset($pool[$reference])) continue;
                $seen[$reference] = true;
                $shared = $pool[$reference];
                $row = [
                    'image' => $shared['image'] ?? [],
                    'alt' => $shared['alt'] ?? '',
                    'title' => $shared['title'] ?? '',
                    'text' => $kind === 'features' ? ($shared['title'] ?? '') : ($row['text'] ?? ''),
                ];
            }
            $text = (string)($row['text'] ?? '');
            if (!self::hasText($text, $page)) continue;
            if ($kind === 'benefits' && trim((string)($row['title'] ?? '')) === '') continue;
            $resolved[] = $row;
        }
        if ($kind === 'benefits') $resolved = array_slice($resolved, 0, 5);
        return Structure::factory($resolved, ['parent' => $page]);
    }

    /** Offer every entry in scope and retain unavailable references for editing. */
    public static function options(Page $page, string $kind, ?string $field = null): array
    {
        $field ??= $kind === 'features' ? 'kitchen_features' : 'benefits_items';
        $selected = array_column(self::rows($page->content()->get($field)->value()), 'reference');
        if ($page->version('changes')->exists()) {
            $pending = self::rows($page->version('changes')->content()->get($field)->value());
            $selected = array_unique([...$selected, ...array_column($pending, 'reference')]);
        }
        $pool = self::availablePool($page, $kind);
        $kitchenKeys = array_keys(self::pool($page->site(), 'kitchen_' . $kind));
        $options = [];
        foreach ($pool as $key => $row) {
            $options[] = [
                'value' => $key,
                'text' => (string)($row['title'] ?? ''),
                'info' => in_array($key, $kitchenKeys, true) ? 'Только для кухонь' : 'Для всего сайта',
            ];
        }
        foreach (array_diff($selected, array_keys($pool)) as $key) {
            $options[] = ['value' => $key, 'text' => 'Недоступный элемент', 'info' => 'Удалите из подборки или проверьте библиотеку'];
        }
        return $options;
    }

    public static function identify(array $rows): array
    {
        foreach ($rows as &$row) {
            unset($row['archived']);
            if (empty($row['key'])) $row['key'] = bin2hex(random_bytes(12));
        }
        return $rows;
    }

    public static function validateSelections(mixed $value): bool
    {
        $keys = array_column(self::rows($value), 'reference');
        return count($keys) === count(array_unique($keys));
    }

    public static function validateLibrary(array $rows, Page $owner, string $kind): bool
    {
        $site = $owner->site();
        $keys = array_column($rows, 'key');
        if (count($keys) !== count(array_unique($keys))) {
            throw new InvalidArgumentException(message: 'Повторяющийся элемент библиотеки. Удалите копию и добавьте новый элемент.');
        }
        $removed = array_diff(array_keys(self::pool($site, $kind)), $keys);
        if ($removed === []) return true;
        foreach ($site->index(true) as $page) {
            foreach (['latest', 'changes'] as $version) {
                if (!$page->version($version)->exists()) continue;
                // Inspect all fields so reused blueprints can choose their own field names.
                foreach ($page->version($version)->content()->data() as $value) {
                    if (!is_string($value) || !str_contains($value, 'reference:')) continue;
                    try {
                        $selections = self::rows($value);
                    } catch (\Exception) {
                        // Prose can mention reference: without containing a structure field.
                        continue;
                    }
                    foreach ($selections as $selection) {
                        if (in_array($selection['reference'] ?? '', $removed, true)) {
                            throw new InvalidArgumentException(message: 'Элемент используется на странице «' . $page->title()->value() . '». Сначала удалите его из подборок на страницах и сохраните изменения, затем удалите из библиотеки.');
                        }
                    }
                }
            }
        }
        return true;
    }
}
