<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';
require dirname(__DIR__) . '/tools/migrate-section-library.php';

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Data\Yaml;
use Kirby\Filesystem\Dir;
use Kirby\Form\Form;
use Studio\Sections\Library;
use function Studio\Tools\migrateSectionLibrary;

function relative_url(string $path): string { return $path; }

$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/studio-section-test-' . bin2hex(random_bytes(8));
mkdir($temporary, 0700, true);
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};

try {
    Dir::copy($root . '/content', $temporary . '/content');
    $kirby = new App([
        'roots' => ['index' => $root, 'content' => $temporary . '/content', 'cache' => $temporary . '/cache', 'media' => $temporary . '/media', 'sessions' => $temporary . '/sessions'],
        'urls' => ['index' => 'http://localhost:8000'],
        'options' => ['api.allowImpersonation' => true],
    ]);
    $kirby->impersonate('kirby');
    $site = $kirby->site();
    $atelier = $site->find('fabrics/aster-cucine/atelier');
    $fixtureImage = $atelier->images()->first()->uuid()->toString();
    $migrationPage = Page::create([
        'parent' => $atelier->parent(), 'slug' => 'migration-fixture', 'template' => 'kuhnya',
        'content' => ['title' => 'Migration fixture',
            'kitchen_features' => Yaml::encode([['text' => 'Тестовая деталь', 'image' => [$fixtureImage]]]),
            'benefits_items' => Yaml::encode([['title' => 'Тестовое преимущество', 'image' => [$fixtureImage], 'text' => 'Уникальный текст']]),
        ],
    ]);
    $before = [];
    foreach ($site->index(true) as $page) {
        foreach (['features', 'benefits'] as $kind) {
            $before[$page->id()][$kind] = array_map(static fn ($row) => [$row->title()->value(), $row->text()->studioText(true), $row->image()->toFile()?->sha1()], iterator_to_array(Library::resolve($page, $kind)));
        }
    }
    $source = file_get_contents($atelier->version('latest')->contentFile());
    $dry = migrateSectionLibrary($kirby);
    $assert(!$dry['applied'] && file_get_contents($atelier->version('latest')->contentFile()) === $source, 'Dry run does not change page content');
    $migration = migrateSectionLibrary($kirby, true);
    $site = $kirby->site();
    $atelier = $site->find('fabrics/aster-cucine/atelier');
    if ($migration['applied']) {
        $assert(is_file($migration['backup'] . '/2_fabrics/aster-cucine/_drafts/migration-fixture/kuhnya.txt'), 'Migration makes a recoverable backup');
    }
    $assert(migrateSectionLibrary($kirby, true)['pages'] === [], 'Migration is idempotent');
    foreach ($site->index(true) as $page) {
        foreach (['features', 'benefits'] as $kind) {
            $after = array_map(static fn ($row) => [$row->title()->value(), $row->text()->studioText(true), $row->image()->toFile()?->sha1()], iterator_to_array(Library::resolve($page, $kind)));
            // Features previously had no title field; their visible labels stay identical.
            if ($kind === 'features') {
                foreach ($after as &$row) $row[0] = null;
                unset($row);
                foreach ($before[$page->id()][$kind] as &$row) $row[0] = null;
                unset($row);
            }
            $assert($before[$page->id()][$kind] === $after, $page->id() . ': migration preserves rendered ' . $kind);
        }
    }
    $owner = Library::owner($site);
    $features = Library::availablePool($atelier, 'features');
    $benefits = Library::availablePool($atelier, 'benefits');
    $assert(count($features) >= 5 && count($benefits) >= 4, 'Existing content seeds both pools');
    foreach (Library::FIELDS as $kind => $_) {
        foreach (Library::pool($site, $kind) as $item) {
            $image = (new Kirby\Content\Field($site, 'image', $item['image'] ?? []))->toFile();
            $assert($image === null || $image->parent() instanceof Kirby\Cms\Site, 'Shared images belong to site, independent of source page');
        }
    }
    $generalPage = $site->find('home');
    $kitchenBenefits = Library::pool($site, 'kitchen_benefits');
    $generalOptions = array_column($generalPage->studioBenefitOptions(), 'value');
    $kitchenOptions = array_column($atelier->studioBenefitOptions(), 'value');
    $assert(array_intersect(array_keys($kitchenBenefits), $generalOptions) === [], 'Kitchen-only benefits are not offered on other pages');
    $assert(array_diff(array_keys($kitchenBenefits), $kitchenOptions) === [], 'Kitchen picker includes kitchen-specific benefits');
    $assert(array_diff($generalOptions, $kitchenOptions) === [], 'Kitchens can also select site-wide benefits');
    $assert($generalPage->studioFeatureOptions() === [], 'Details are offered only on kitchen pages');
    $assert(array_column($atelier->studioFeatureOptions(), 'value') === array_keys($features), 'Kitchens receive their kitchen details');
    $featureKey = array_key_first($features);
    $benefitKey = array_key_first($benefits);
    $newPage = static fn (array $content): Page => new Page(['slug' => 'section-fixture', 'template' => 'kuhnya', 'content' => ['title' => 'Test', ...$content]]);
    $selection = static fn (string $text): string => Yaml::encode([['reference' => $benefitKey, 'text' => $text]]);
    foreach (['', '   ', "\n\t", "\u{00A0}\u{200B}", '<p><br></p>', '{"type":"doc","content":[{"type":"paragraph"}]}', '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"  "}]}]}'] as $empty) {
        $page = $newPage(['benefits_items' => $selection($empty)]);
        $assert($page->studioBenefits()->isEmpty(), 'Empty or formatting-only paragraph hides benefit');
        $assert(trim(snippet('benefits', ['page' => $page], true)) === '', 'No empty section or placeholder emitted');
    }
    $page = $newPage(['benefits_items' => $selection('Текст только для этой кухни')]);
    $other = $newPage(['benefits_items' => $selection('Другой текст')]);
    $assert($page->studioBenefits()->first()->text()->value() !== $other->studioBenefits()->first()->text()->value(), 'Descriptions stay page-specific');
    $assert(str_contains(snippet('benefits', ['page' => $page], true), 'Текст только для этой кухни'), 'Visitor snippet resolves shared identity and local prose');
    $page = $newPage(['benefits_items' => Yaml::encode([['reference' => 'missing', 'text' => 'hidden'], ['reference' => $benefitKey, 'text' => 'one'], ['reference' => $benefitKey, 'text' => 'duplicate']])]);
    $assert($page->studioBenefits()->count() === 1, 'Missing references and duplicates never render');
    $rows = array_map(static fn ($key) => ['reference' => $key, 'text' => 'Visible'], array_keys($benefits));
    $rows[0]['text'] = '';
    $page = $newPage(['benefits_items' => Yaml::encode($rows)]);
    $assert($page->studioBenefits()->count() === 5, 'Five-card limit applies after filtering unfinished cards');
    $page = $newPage(['kitchen_features' => Yaml::encode([['reference' => $featureKey]])]);
    $features[$featureKey]['title'] = 'Обновлённое название';
    $features[$featureKey]['archived'] = true;
    $owner = $owner->save(['kitchen_feature_library' => Yaml::encode(array_reverse(array_values($features)))]);
    $assert($page->studioFeatures()->first()->text()->value() === 'Обновлённое название', 'Rename and library reorder preserve reference identity');
    $assert($page->studioFeatures()->count() === 1, 'Legacy archive flags do not affect existing selections');
    $form = Form::for($atelier);
    $props = $form->fields()->toProps();
    $assert($props['kitchen_features']['type'] === 'studio-features' && count($props['kitchen_features']['options']) >= 5, 'Native multiselect receives scoped query options');
    $assert($props['benefits_items']['type'] === 'structure' && $props['benefits_items']['fields']['reference']['type'] === 'select', 'Benefits use native structure and select fields');
    $assert(count($form->fields()->get('kitchen_features')->toFormValue()) === $atelier->studioFeatures()->count(), 'Existing structure references appear as native multiselect values');
    $availableOptions = array_column($newPage([])->studioFeatureOptions(), 'text', 'value');
    $assert(($availableOptions[$featureKey] ?? null) === 'Обновлённое название', 'Every library entry is selectable without archive labels, including legacy archived entries');
    $assert($props['benefits_items']['fields']['text']['type'] === 'studio-tiptap', 'Benefit cards use existing rich text editor');
    $link = $kirby->api()->call('pages/fabrics+aster-cucine+atelier/fields/benefits_items+text/process-kirbytag', 'POST', ['body' => ['kirbyTag' => '(link: https://example.com text: Подробнее)']]);
    $assert(str_contains($link['text'], 'Подробнее'), 'Nested editor link route remains reachable through native field API');
    $input = [['reference' => $benefitKey, 'text' => '']];
    $form->submit(['benefits_items' => $input], passthrough: true);
    $stored = Library::rows($form->toStoredValues()['benefits_items']);
    $assert($stored === $input, 'Saving unfinished selection retains empty paragraph without shared defaults');
    $form->submit(['benefits_items' => []], passthrough: true);
    $assert(Library::rows($form->toStoredValues()['benefits_items']) === [], 'Clearing all selections persists');
    $form->submit(['kitchen_features' => [$featureKey]], passthrough: true);
    $assert(Library::rows($form->toStoredValues()['kitchen_features']) === [['reference' => $featureKey]], 'Native multiselect stores stable references');
    $form->submit(['kitchen_features' => []], passthrough: true);
    $assert(Library::rows($form->toStoredValues()['kitchen_features']) === [], 'Clearing native multiselect persists');
    $form->submit(['benefits_items' => [$input[0], $input[0]]], passthrough: true);
    $assert($form->fields()->get('benefits_items')->errors() !== [], 'Native structure rejects duplicate benefit references');
    $libraryForm = Form::for($owner);
    $libraryForm->submit(['kitchen_feature_library' => [...array_values($features), ['title' => 'Новая деталь']]], passthrough: true);
    $savedPool = Library::rows($libraryForm->toStoredValues()['kitchen_feature_library']);
    $newKey = $savedPool[array_key_last($savedPool)]['key'] ?? '';
    $assert(strlen($newKey) === 24 && $savedPool[0]['key'] === $featureKey, 'Library automatically assigns stable keys to new entries');
    $blocked = false;
    try { Library::validateLibrary([], $owner, 'kitchen_features'); } catch (Kirby\Exception\InvalidArgumentException $error) { $blocked = str_contains($error->getMessage(), 'используется'); }
    $assert($blocked, 'Deleting an in-use entry identifies the page that must be updated first');
    $owner->version('changes')->save(['kitchen_feature_library' => Yaml::encode([])]);
    $previousRenderVersion = Kirby\Content\VersionId::$render;
    try {
        Kirby\Content\VersionId::$render = new Kirby\Content\VersionId('changes');
        $assert(count(Library::pool($site, 'kitchen_features')) === count($features), 'Unpublished library changes do not replace saved shared identities');
        $blocked = false;
        try { Library::validateLibrary([], $owner, 'kitchen_features'); } catch (Kirby\Exception\InvalidArgumentException) { $blocked = true; }
        $assert($blocked, 'Saved references remain protected after a deletion reaches pending Panel changes');
    } finally {
        Kirby\Content\VersionId::$render = $previousRenderVersion;
    }
    echo "Section library: {$checks} checks passed.\n";
} finally {
    if (isset($migration['backup'])) Dir::remove($migration['backup']);
    if (isset($kirby)) $kirby->session()->destroy();
    Dir::remove($temporary);
}
