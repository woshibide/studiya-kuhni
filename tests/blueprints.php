<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';

use Kirby\Cms\App;
use Kirby\Cms\Blueprint;
use Kirby\Cms\File;
use Kirby\Cms\Files;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Section;
use Kirby\Data\Yaml;
use Kirby\Filesystem\Dir;
use Kirby\Form\Field as FormField;
use Kirby\Form\Form;
use Symfony\Component\Yaml\Yaml as StrictYaml;

$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/studio-blueprints-' . bin2hex(random_bytes(8));
mkdir($temporary, 0700, true);
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};

try {
    // Panel serialization can create missing file UUIDs, so all content is isolated.
    Dir::copy($root . '/content', $temporary . '/content');
    $kirby = new App([
        'roots' => [
            'index' => $root,
            'content' => $temporary . '/content',
            'cache' => $temporary . '/cache',
            'sessions' => $temporary . '/sessions',
            'media' => $temporary . '/media',
        ],
        'urls' => ['index' => 'http://localhost:8000'],
    ]);
    $kirby->impersonate('kirby');

    preg_match_all('/<symbol\s+id="icon-([^"]+)"/', file_get_contents($root . '/kirby/panel/dist/img/icons.svg'), $iconMatches);
    $icons = array_fill_keys($iconMatches[1], true);
    $assert(count($icons) > 100, 'Panel icon validation uses the installed Kirby sprite');
    $validateNode = static function (array $node, string $context, ?string $kind = null) use (&$validateNode, $assert, $icons): void {
        if (isset($node['icon']) && is_string($node['icon'])) {
            $assert(isset($icons[$node['icon']]), $context . ' uses an unavailable Panel icon: ' . $node['icon']);
        }
        if (isset($node['type']) && $kind !== null) {
            $registered = $kind === 'section' ? Section::$types : FormField::$types;
            $assert(isset($registered[$node['type']]) || ($kind === 'field' && $node['type'] === 'group'), $context . ' uses an unregistered ' . $kind . ' type: ' . $node['type']);
        }
        if (isset($node['layout']) && is_string($node['layout'])) {
            $layouts = $kind === 'field' ? ['list', 'cardlets', 'cards'] : ['list', 'cardlets', 'cards', 'table'];
            $assert(in_array($node['layout'], $layouts, true), $context . ' uses an unsupported layout');
        }
        foreach ((array)($node['extends'] ?? []) as $reference) {
            $assert(is_array(Blueprint::find($reference)), $context . ' blueprint extension must exist: ' . $reference);
        }
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                if ($key === 'fields' || $key === 'sections') {
                    foreach ($value as $name => $definition) {
                        if (is_array($definition)) $validateNode($definition, $context . '/' . $key . '/' . $name, $key === 'fields' ? 'field' : 'section');
                    }
                } else {
                    $validateNode($value, $context . '/' . $key);
                }
            } elseif (is_string($value) && preg_match('~^(fields|tabs|page-options|blocks)/[a-z0-9-]+$~', $value)) {
                $assert(is_array(Blueprint::find($value)), $context . ' blueprint reference must exist: ' . $value);
            }
        }
    };

    $sources = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/site/blueprints'));
    $yamlCount = 0;
    foreach ($sources as $source) {
        if ($source->getExtension() !== 'yml') continue;
        $text = file_get_contents($source->getPathname());
        $assert(!str_contains($text, "\t"), $source->getFilename() . ' must use spaces for YAML indentation');
        $data = Yaml::decode($text);
        $assert(is_array($data), $source->getFilename() . ' must parse as a blueprint');
        $assert(is_array(StrictYaml::parse($text)), $source->getFilename() . ' must parse without duplicate YAML keys');
        $relative = substr($source->getPathname(), strlen($root . '/site/blueprints/'));
        $validateNode($data, $relative, str_starts_with($relative, 'fields/') ? 'field' : null);
        $yamlCount++;
    }

    $site = $kirby->site();
    $models = [$site, ...$site->index(true)->values()];
    foreach (glob($root . '/site/blueprints/pages/*.yml') as $source) {
        $template = basename($source, '.yml');
        $models[] = new Page([
            'slug' => 'blueprint-check-' . $template,
            'template' => $template,
            'content' => ['title' => 'Blueprint check'],
        ]);
    }

    $sectionCount = 0;
    $validateErrorProps = static function (array $props, string $context) use (&$validateErrorProps, $assert): void {
        if (($props['type'] ?? null) === 'info') {
            $assert(($props['theme'] ?? null) !== 'negative', $context . ' contains a Kirby error placeholder');
        }
        if (array_key_exists('status', $props)) $assert($props['status'] !== 'error', $context . ' contains an editor error status');
        if (array_key_exists('error', $props)) $assert(empty($props['error']), $context . ' contains an editor error');
        foreach ($props as $key => $value) {
            if (is_array($value)) $validateErrorProps($value, $context . '/' . $key);
        }
    };
    $validateFields = static function (array $fields, $model, string $context) use (&$validateFields, $assert): void {
        foreach ($fields as $name => $field) {
            $path = $context . '/' . $name;
            $assert(($field['theme'] ?? null) !== 'negative', $path . ' must not be a Kirby blueprint error placeholder');
            $assert(isset(FormField::$types[$field['type']]), $path . ' field type must be registered at runtime');
            if (isset($field['query']) && in_array($field['type'], ['files', 'pages'], true)) {
                $result = $model->query($field['query']);
                $assert($field['type'] === 'files' ? $result instanceof Files : $result instanceof Pages, $path . ' picker query must return the expected collection');
            }
            if (isset($field['uploads']['template'])) {
                $assert(is_array(Blueprint::find('files/' . $field['uploads']['template'])), $path . ' upload blueprint must exist');
            }
            if (isset($field['fields'])) $validateFields($field['fields'], $model, $path);
        }
    };
    foreach ($models as $model) {
        $blueprint = $model->blueprint();
        $context = $model->id() ?: 'site';
        $validateFields($blueprint->fields(), $model, $context);
        foreach (['seo_title', 'seo_description', 'seo_image'] as $field) {
            $assert($blueprint->field($field) !== null, $context . ' must expose ' . $field);
        }
        if ($model instanceof Page) {
            $assert($blueprint->field('seo_noindex') !== null, $context . ' must expose page indexing control');
        }
        $assert(
            $model->query($blueprint->field('seo_image')['query']) instanceof Files,
            $context . ' social image picker must resolve files in its own context'
        );

        foreach ($blueprint->sections() as $name => $section) {
            $assert(isset(Section::$types[$section->type()]), $context . '/' . $name . ' section type must be registered at runtime');
            $validateErrorProps($section->toArray(), $context . '/sections/' . $name);
            $sectionCount++;
        }
        $formProps = Form::for($model)->fields()->toProps();
        $assert($formProps !== [], $context . ' editor form must instantiate');
        $validateErrorProps($formProps, $context . '/form');
    }

    $rootIds = [];
    foreach ($site->blueprint()->sections() as $section) {
        if ($section->type() !== 'pages') continue;
        if (!$section->parent() instanceof Kirby\Cms\Site) continue;
        foreach ($section->models() as $page) $rootIds[] = $page->id();
    }
    $expectedRootIds = $site->childrenAndDrafts()->keys();
    sort($rootIds);
    sort($expectedRootIds);
    $assert($rootIds === $expectedRootIds, 'Every root page must appear exactly once in the site editor');

    foreach (['home', 'fabrics', 'designers', 'proizvodstvo', 'contacts', 'faq', 'archive', 'mediakit', 'privacy', 'error'] as $id) {
        $page = $site->find($id);
        $assert($page !== null, $id . ' fixed route must exist');
        $options = $page->blueprint()->options();
        foreach (['changeSlug', 'changeTemplate', 'delete', 'duplicate', 'move'] as $option) {
            $assert($options[$option] === false, $id . ' must protect ' . $option);
        }
        if ($page->isHomeOrErrorPage()) {
            $assert($options['changeStatus'] === false, $id . ' must remain published');
        } else {
            $assert(
                array_keys($page->blueprint()->status()) === ['draft', 'unlisted', 'listed'],
                $id . ' must retain draft and both published statuses'
            );
        }
    }

    $assert($site->find('fabrics')->intendedTemplate()->name() === 'fabrics', 'Catalogue must use its intended blueprint');
    $assert($site->find('fabrics')->blueprint()->section('brands')->templates() === ['fabric'], 'Catalogue can create factories only');
    $assert($site->blueprint()->section('factories')->parent()->id() === 'fabrics', 'Site catalogue shortcut edits the real factory collection');
    $serviceRows = array_column($site->blueprint()->section('service_pages')->data(), null, 'id');
    $assert($serviceRows['error']['text'] === $site->find('error')->blueprint()->title(), 'Error page uses a recognizable service label without changing its content title');
    foreach ([$site->blueprint()->section('factories'), $site->find('fabrics')->blueprint()->section('brands')] as $section) {
        foreach ($section->data() as $item) {
            $assert(($item['image']['back'] ?? null) === 'white', 'Factory logo previews retain contrast in dark Panel mode');
        }
    }
    $homeFields = Form::for($site->find('home'))->fields()->toProps();
    $assert(($homeFields['brands_items']['fields']['logo']['image']['back'] ?? null) === 'white', 'Shared brand logo picker uses a white background');
    $richTextProps = Form::for($site->find('privacy'))->fields()->toProps()['text'];
    $assert(in_array('file', $richTextProps['links']['options'], true), 'Rich text link dialog allows existing file links');
    $assert($richTextProps['uploads'] === false, 'Rich text file links do not enable inline uploads');
    foreach (['home', 'fabrics', 'proizvodstvo'] as $id) {
        foreach (['cta_warmup_heading', 'cta_warmup_text', 'cta_warmup_button_text', 'cta_warmup_image'] as $field) {
            $assert($site->find($id)->blueprint()->field($field) !== null, $id . ' exposes its rendered consultation field ' . $field);
        }
    }
    $assert($site->find('archive')->blueprint()->section('posts')->templates() === ['archive-post'], 'Archive can create articles only');
    $factory = new Page(['slug' => 'blueprint-check-factory', 'template' => 'fabric']);
    $assert($factory->blueprint()->section('pages')->templates() === ['kuhnya'], 'Factory can create kitchens only');
    $assert($factory->blueprint()->tab()['name'] === 'collections', 'Factory editor opens its kitchen collection first');
    $assert($factory->blueprint()->tab('location') !== null, 'Factory map has a dedicated editor tab');

    $legacy = new Page([
        'slug' => 'legacy-editor-fields',
        'template' => 'default',
        'content' => ['title' => 'Before', 'cta_heading' => 'Keep legacy heading', 'cta_text' => 'Keep legacy text'],
    ]);
    $form = Form::for($legacy);
    $form->submit(['title' => 'After']);
    $values = $form->toStoredValues();
    $assert($values['cta_heading'] === 'Keep legacy heading' && $values['cta_text'] === 'Keep legacy text', 'Removing unused controls preserves their stored values on a normal form save');
    $fresh = new Page(['slug' => 'fresh-editor-fields', 'template' => 'default', 'content' => ['title' => 'New']]);
    $freshForm = Form::for($fresh);
    $freshForm->submit(['title' => 'Changed']);
    $assert(!array_key_exists('cta_heading', $freshForm->toStoredValues()), 'Removed unused controls never materialize defaults');
    $legacyMap = new Page([
        'slug' => 'legacy-map-fields', 'template' => 'fabric',
        'content' => ['title' => 'Factory', 'fabric_map_lat' => '42.653145', 'fabric_map_lng' => '14.037308'],
    ]);
    $mapForm = Form::for($legacyMap);
    $mapForm->submit(['title' => 'Renamed factory']);
    $assert($mapForm->toStoredValues()['fabric_map_lat'] === '42.653145' && $mapForm->toStoredValues()['fabric_map_lng'] === '14.037308', 'Migrated legacy coordinates survive editor cleanup unchanged');

    $kitchen = $site->index(true)->filter(static fn ($page) =>
        $page->intendedTemplate()->name() === 'kuhnya' &&
        $page->images()->filter(static fn ($image) => strtolower($image->extension()) !== 'svg')->count() >= 2
    )->first();
    $assert($kitchen !== null, 'Gallery order regression has a real kitchen with multiple photos');
    $photos = $kitchen->images()->filter(static fn ($image) => strtolower($image->extension()) !== 'svg')->sorted();
    $chosen = array_reverse($photos->limit(2)->values());
    $chosenIds = array_map(static fn ($image) => $image->id(), $chosen);
    $kitchen = $kitchen->update([
        'kitchen_gallery_images' => Yaml::encode(array_map(static fn ($image) => $image->uuid()->toString(), $chosen)),
    ]);
    $assert($kitchen->studioKitchenImages()->keys() === $chosenIds, 'Selected photo order takes priority over file sorting');
    $previewQuery = $factory->blueprint()->section('pages')->image()['query'];
    $assert($kitchen->query($previewQuery)?->id() === $chosenIds[0], 'Panel kitchen card uses the same selected cover as the website');
    $kitchen->render();
    $card = snippet('kuhnya-card-overview', ['kuhnya' => $kitchen], true);
    $assert(preg_match('/<img\b[^>]*\bsrc="([^"]+)"/', $card, $cardImage) === 1, 'Kitchen card renders its cover');
    $cover = $chosen[0]->width() > 960 ? $chosen[0]->resize(960) : $chosen[0];
    $assert(html_entity_decode($cardImage[1], ENT_QUOTES) === relative_url($cover->url()), 'Rendered kitchen card honors the first selected photo');

    $svgSource = $temporary . '/preview-icon.svg';
    file_put_contents($svgSource, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>');
    File::create(['source' => $svgSource, 'parent' => $kitchen, 'filename' => 'preview-icon.svg', 'template' => 'fabric-logo']);
    $kitchen = $kitchen->update(['kitchen_gallery_images' => '']);
    $assert($kitchen->images()->filterBy('extension', 'svg')->count() > 0, 'Fallback fixture includes an SVG that cannot be a kitchen cover');
    $assert($kitchen->studioKitchenImages()->keys() === $photos->keys(), 'Empty selection restores sorted raster photos and excludes SVG');
    $assert($kitchen->query($previewQuery)?->id() === $photos->first()->id(), 'Panel kitchen card also resolves the fallback cover');

    $fileCount = 0;
    foreach ($site->index(true) as $page) {
        foreach ($page->images() as $image) {
            $assert($image->blueprint()->field('alt') !== null, $image->id() . ' must expose image description');
            $assert(isset(Form::for($image)->fields()->toProps()['alt']), $image->id() . ' description field must render');
            $fileCount++;
        }
    }
    foreach (['image', 'social-image', 'fabric-logo'] as $template) {
        $file = new File(['filename' => 'example.jpg', 'parent' => $site->find('home'), 'template' => $template]);
        $mimeTypes = $file->blueprint()->accept()['mime'];
        $assert(in_array('image/png', $mimeTypes, true), $template . ' must accept PNG');
        $assert(in_array('image/svg+xml', $mimeTypes, true) === ($template === 'fabric-logo'), 'Only logo uploads accept SVG');
    }

    $article = new Page(['slug' => 'blueprint-check-article', 'template' => 'archive-post']);
    $fieldsets = Form::for($article)->fields()->toProps()['blocks']['fieldsets'];
    $heading = $fieldsets['heading']['tabs']['content']['fields']['level'];
    $assert(!in_array('h1', array_column($heading['options'], 'value'), true), 'Article blocks cannot add a second H1');
    $assert($heading['default'] === 'h2', 'Article headings start at H2');
    foreach ($site->index(true) as $page) {
        foreach ($page->blocks()->toBlocks() as $block) {
            $assert(isset($fieldsets[$block->type()]), 'Existing block type ' . $block->type() . ' must remain editable');
        }
    }

    $report = "Blueprints: {$checks} checks passed on Kirby " . App::version() . ".\n";
    $report .= "Validated {$yamlCount} YAML files, " . count($models) . " editor models, {$sectionCount} sections and {$fileCount} image editors.\n";
} finally {
    if (isset($kirby)) $kirby->session()->destroy();
    Dir::remove($temporary);
}

echo $report;
