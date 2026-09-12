<?php

namespace Studio\Tools;

use Kirby\Cms\App;
use Kirby\Content\Field;
use Kirby\Data\Yaml;
use Studio\RichText;
use Studio\Sections\Library;

require_once dirname(__DIR__) . '/kirby/bootstrap.php';
require_once dirname(__DIR__) . '/site/plugins/studio-sections/Library.php';

/** Promotes section identities into scoped pools; keeps descriptions on pages. */
function migrateSectionLibrary(App $kirby, bool $apply = false): array
{
    $site = $kirby->site();
    $owner = Library::owner($site);
    $pools = [];
    $oldFields = [];
    $sharedKeys = [];
    foreach ($site->index(true) as $page) {
        if ($page->intendedTemplate()->name() === 'kuhnya') continue;
        foreach (['latest', 'changes'] as $version) {
            if (!$page->version($version)->exists()) continue;
            foreach (Library::rows($page->version($version)->content()->get('benefits_items')->value()) as $row) {
                if (!empty($row['reference'])) $sharedKeys[$row['reference']] = true;
            }
        }
    }
    $images = [];
    $updates = [];
    foreach (Library::FIELDS as $kind => $field) {
        $pools[$kind] = [];
        foreach (Library::rows($owner->content()->get($field)->value()) as $row) $pools[$kind][$row['key']] = $row;
    }
    // Upgrade the initial site-owned library without changing any existing keys.
    foreach (['features' => 'feature_library', 'benefits' => 'benefit_library'] as $kind => $field) {
        foreach (Library::rows($site->content()->get($field)->value()) as $row) {
            $target = $kind === 'benefits' && isset($sharedKeys[$row['key']]) ? 'benefits' : 'kitchen_' . $kind;
            $pools[$target][$row['key']] ??= $row;
            $oldFields[$field] = '';
        }
    }

    foreach ($site->index(true) as $page) {
        foreach (['features' => 'kitchen_features', 'benefits' => 'benefits_items'] as $kind => $field) {
            if ($kind === 'features' && $page->intendedTemplate()->name() !== 'kuhnya') continue;
            $target = $page->intendedTemplate()->name() === 'kuhnya' ? 'kitchen_' . $kind : $kind;
            $rows = Library::rows($page->content()->get($field)->value());
            $selections = [];
            $changed = false;
            foreach ($rows as $row) {
                if (!empty($row['reference'])) { $selections[] = $row; continue; }
                $title = $kind === 'features'
                    ? RichText::plain(new Field($page, 'text', $row['text'] ?? ''))
                    : trim((string)($row['title'] ?? ''));
                // Empty legacy placeholders contain no reusable identity.
                if ($title === '') { $selections[] = $row; continue; }
                $image = (new Field($page, 'image', $row['image'] ?? []))->toFile();
                if (!$image && !empty($row['image'])) throw new \RuntimeException('Missing image on ' . $page->id());
                $alt = (string)($row['alt'] ?? '');
                $key = substr(hash('sha256', $kind . "\0" . $title . "\0" . ($image?->sha1() ?? '') . "\0" . $alt), 0, 24);
                if (!isset($pools[$target][$key])) {
                    $pools[$target][$key] = ['key' => $key, 'title' => $title, 'image' => $row['image'] ?? [], 'alt' => $alt];
                    if ($image) $images[$target][$key] = $image;
                }
                $selections[] = $kind === 'features' ? ['reference' => $key] : ['reference' => $key, 'text' => (string)($row['text'] ?? '')];
                $changed = true;
            }
            if ($changed) {
                if ($page->version('changes')->exists()) throw new \RuntimeException('Unfinished Panel changes on ' . $page->id() . '. Save or discard them before migration.');
                $updates[$page->id()][$field] = Yaml::encode($selections);
            }
        }
    }

    // A benefit used in both scopes belongs to the shared pool.
    foreach ($pools['benefits'] as $key => $_) unset($pools['kitchen_benefits'][$key]);
    $poolChanged = $oldFields !== [];
    foreach (Library::FIELDS as $kind => $field) {
        if (Library::rows($owner->content()->get($field)->value()) !== array_values($pools[$kind])) $poolChanged = true;
    }
    $result = ['pages' => array_keys($updates), 'pools' => array_map('count', $pools), 'applied' => false];
    if (!$apply || (!$poolChanged && $updates === [])) return $result;
    if ($poolChanged && $owner->version('changes')->exists()) throw new \RuntimeException('Unfinished library changes on fabrics. Save or discard them before migration.');
    if ($oldFields !== [] && $site->version('changes')->exists()) throw new \RuntimeException('Unfinished site settings. Save or discard them before migration.');

    $backup = sys_get_temp_dir() . '/studio-section-library-backup-' . bin2hex(random_bytes(8));
    mkdir($backup, 0700, true);
    $paths = [$site->version('latest')->contentFile(), $owner->version('latest')->contentFile()];
    foreach ($updates as $id => $_) $paths[] = $site->index(true)->find($id)->version('latest')->contentFile();
    foreach ($paths as $path) {
        $relative = substr(realpath($path), strlen(realpath($kirby->root('content'))) + 1);
        $target = $backup . '/' . $relative;
        if (!is_dir(dirname($target))) mkdir(dirname($target), 0700, true);
        if (!copy($path, $target)) throw new \RuntimeException('Could not back up ' . $path);
    }

    $kirby->impersonate('kirby', static function () use ($site, $owner, &$pools, $images, $updates, $oldFields): void {
        foreach ($images as $kind => $entries) {
            foreach ($entries as $key => $source) {
                if (!isset($pools[$kind][$key])) continue;
                $name = 'library-' . $source->sha1() . '.' . $source->extension();
                $image = $site->file($name) ?? $site->createFile([
                    'source' => $source->root(), 'filename' => $name, 'template' => 'section-icon',
                    'content' => ['alt' => $source->alt()->value()],
                ]);
                $pools[$kind][$key]['image'] = [$image->uuid()->toString()];
            }
        }
        $data = [];
        foreach (Library::FIELDS as $kind => $field) $data[$field] = Yaml::encode(array_values($pools[$kind]));
        // Save the pool before references; any interrupted run remains readable and can be repeated.
        $owner->version('latest')->update([...($updates[$owner->id()] ?? []), ...$data]);
        foreach ($updates as $id => $data) {
            if ($id !== $owner->id()) $site->index(true)->find($id)->version('latest')->update($data);
        }
        if ($oldFields !== []) $site->version('latest')->update($oldFields);
    });
    return [...$result, 'applied' => true, 'backup' => $backup];
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) return;
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (!in_array(array_slice($argv, 1), [[], ['--apply']], true)) {
    fwrite(STDERR, "Usage: php tools/migrate-section-library.php [--apply]\n");
    exit(1);
}
$kirby = new App(['roots' => ['index' => dirname(__DIR__)]]);
echo json_encode(migrateSectionLibrary($kirby, in_array('--apply', $argv, true)), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
