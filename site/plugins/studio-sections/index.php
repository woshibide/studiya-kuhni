<?php

use Kirby\Cms\App;
use Studio\Sections\Library;

require_once __DIR__ . '/Library.php';

App::plugin('studio/sections', [
    'fields' => [
        'studio-library' => [
            'extends' => 'structure',
            'props' => [
                'kind' => fn (string $kind = 'kitchen_features') => $kind,
            ],
            'computed' => [
                'value' => function () { return Library::identify($this->rows($this->value)); },
            ],
            'validations' => [
                'library' => function ($value) { return Library::validateLibrary($value, $this->model(), $this->kind()); },
            ],
        ],
        'studio-features' => [
            // Kirby field inheritance is shallow; multiselect itself extends tags.
            'extends' => 'tags',
            'props' => [
                'accept' => fn () => 'options',
                'icon' => fn (string $icon = 'checklist') => $icon,
            ],
            'methods' => [
                'toValues' => function ($value) {
                    $values = is_array($value) ? $value : Library::rows($value);
                    if (isset($values[0]) && is_array($values[0])) $values = array_column($values, 'reference');
                    return $this->sanitizeOptions($values);
                },
            ],
            'save' => fn ($value) => array_map(static fn ($key) => ['reference' => $key], $value ?? []),
        ],
    ],
    'translations' => [
        'ru' => ['error.validation.sectionreferences' => 'Преимущество уже выбрано. Удалите повторяющуюся строку.'],
        'en' => ['error.validation.sectionreferences' => 'This benefit is already selected. Remove the duplicate row.'],
    ],
    'validators' => [
        'sectionReferences' => fn ($value) => Library::validateSelections($value),
    ],
    'pageMethods' => [
        'studioFeatures' => function (?string $field = null) { return Library::resolve($this, 'features', $field); },
        'studioBenefits' => function (?string $field = null) { return Library::resolve($this, 'benefits', $field); },
        'studioFeatureOptions' => function (?string $field = null) { return Library::options($this, 'features', $field); },
        'studioBenefitOptions' => function (?string $field = null) { return Library::options($this, 'benefits', $field); },
    ],
]);
