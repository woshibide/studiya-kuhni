<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';
require_once dirname(__DIR__) . '/site/plugins/studio/Location.php';
require_once dirname(__DIR__) . '/tools/migrate-locations.php';

use Kirby\Cms\App;
use Kirby\Data\Yaml;
use Kirby\Filesystem\Dir;
use Kirby\Form\Form;
use Studio\Location;
use function Studio\Tools\migrateLocations;

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$legacy = ['42.653145', '14.037308', '9', 'Factory'];
$assert(Location::resolve(null, ...$legacy) === ['lat' => 42.653145, 'lng' => 14.037308, 'zoom' => 9, 'label' => 'Factory'], 'Absent Locator uses validated legacy location');
$assert(Location::resolve('', ...$legacy) !== null, 'Absent field with blank raw value can use legacy coordinates');
$assert(Location::resolve('', ...[...$legacy, true]) === null, 'Explicitly cleared Locator suppresses legacy location');
$assert(Location::resolve(null, ...[...$legacy, true]) === null, 'Present null Locator suppresses legacy location');
$assert(Location::resolve("lat: 0\nlon: 0\nzoom: 2", ...$legacy) === ['lat' => 0.0, 'lng' => 0.0, 'zoom' => 2, 'label' => 'Factory'], 'Locator zero coordinates take precedence');
$assert(Location::resolve("lat: -90\nlon: 180", ...$legacy)['zoom'] === 13, 'New location uses own default zoom, not legacy zoom');
$assert(Location::resolve(null, '0', '0', '', '') !== null, 'Legacy zero coordinates are valid');

foreach (['lat: 1', 'lon: 1', "lat: 1\nlon: ", "lat: 91\nlon: 1", "lat: 1\nlon: -181", "lat: .nan\nlon: 1", "lat: .inf\nlon: 1", "lat: false\nlon: 1", "lat: [1]\nlon: 1", "lat: 1\nlat: 2\nlon: 3", '[]', '{}', 'null', 'false', 'text', 'lat: ['] as $locator) {
    $assert(Location::resolve($locator, ...$legacy) === null, 'Invalid or empty Locator never falls back: ' . $locator);
}
foreach ([null, '', ' ', true, false, [], new stdClass(), 'invalid', INF, NAN, '1e999'] as $coordinate) {
    $assert(Location::resolve(null, $coordinate, '0', '', '') === null, 'Invalid legacy latitude suppresses map');
    $assert(Location::resolve(null, '0', $coordinate, '', '') === null, 'Invalid legacy longitude suppresses map');
}
foreach (['-1', '0', '20', '3.5', true, false, null, [], 'not-a-zoom'] as $zoom) {
    $assert(Location::resolve(null, '1', '2', $zoom, '')['zoom'] === 13, 'Invalid zoom uses documented fallback');
}

$temporary = sys_get_temp_dir() . '/studio-map-test-' . bin2hex(random_bytes(8));
mkdir($temporary . '/content/1_fabrics/legacy/kitchen', 0700, true);
mkdir($temporary . '/content/1_fabrics/empty', 0700, true);
mkdir($temporary . '/content/1_fabrics/cleared', 0700, true);
mkdir($temporary . '/content/1_fabrics/located', 0700, true);
mkdir($temporary . '/config', 0700, true);

try {
    $write = static function (string $path, array $fields) use ($temporary): void {
        Kirby\Data\Txt::write($temporary . '/content/' . $path, $fields);
    };
    $write('1_fabrics/fabrics.txt', ['title' => 'Factories']);
    $write('1_fabrics/legacy/fabric.txt', ['title' => 'Legacy factory', 'fabric_map_lat' => '42.653145', 'fabric_map_lng' => '14.037308', 'fabric_map_zoom' => '', 'fabric_info_text' => 'Preserve exact editorial text.']);
    $write('1_fabrics/legacy/kitchen/kuhnya.txt', ['title' => 'Kitchen']);
    $write('1_fabrics/empty/fabric.txt', ['title' => 'Unknown location', 'fabric_map_lat' => '', 'fabric_map_lng' => '']);
    $write('1_fabrics/cleared/fabric.txt', ['title' => 'Cleared location', 'fabric_map_lat' => '10', 'fabric_map_lng' => '20', 'fabric_location' => '']);
    $write('1_fabrics/located/fabric.txt', ['title' => 'Existing Locator', 'fabric_map_lat' => '10', 'fabric_map_lng' => '20', 'fabric_location' => "lat: 30\nlon: 40\nzoom: 8"]);
    $kirby = new App([
        'roots' => [
            'index' => dirname(__DIR__), 'config' => $temporary . '/config',
            'content' => $temporary . '/content', 'cache' => $temporary . '/cache',
            'sessions' => $temporary . '/sessions', 'media' => $temporary . '/media',
        ],
        'urls' => ['index' => 'http://localhost:8000'],
        'options' => ['sylvainjule.locator.mapbox.id' => 'mapbox/outdoors-v11'],
    ]);
    $kirby->impersonate('kirby');
    $snapshot = static function () use ($temporary): array {
        $hashes = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary . '/content')) as $file) {
            if ($file->isFile()) $hashes[$file->getPathname()] = hash_file('sha256', $file->getPathname());
        }
        return $hashes;
    };

    $before = $snapshot();
    $legacyPage = $kirby->page('fabrics/legacy');
    $legacyContent = $legacyPage->content()->toArray();
    $preview = migrateLocations($kirby);
    $assert($snapshot() === $before, 'Dry-run leaves all source files byte-for-byte unchanged');
    $assert(count(array_filter($preview, fn ($row) => $row['status'] === 'would-migrate')) === 1, 'Only legacy page with valid coordinates is eligible');
    $assert($kirby->page('fabrics/empty')->studioMapLocation() === null, 'Missing location has no fabricated default');
    $assert($kirby->page('fabrics/cleared')->studioMapLocation() === null, 'Explicit empty field suppresses legacy in actual page method');
    $assert($kirby->page('fabrics/legacy/kitchen')->studioMapLocation() === $legacyPage->studioMapLocation(), 'Kitchen inherits factory location');

    migrateLocations($kirby, true);
    $kirby = $kirby->clone();
    $kirby->impersonate('kirby');
    $legacyPage = $kirby->page('fabrics/legacy');
    $newContent = $legacyPage->content()->toArray();
    unset($newContent['fabric_location']);
    $assert($newContent === $legacyContent, 'Migration preserves every original content field and value');
    $location = $legacyPage->fabric_location()->toLocation();
    $assert((float)$location->lat()->value() === 42.653145 && (float)$location->lon()->value() === 14.037308, 'Locator API decodes migrated coordinates');
    $assert($legacyPage->studioMapLocation()['zoom'] === 13, 'Absent legacy zoom migrates documented default');
    $after = $snapshot();
    migrateLocations($kirby, true);
    $assert($snapshot() === $after, 'Repeated migration is idempotent');
    $assert($kirby->page('fabrics/cleared')->fabric_location()->value() === '', 'Migration never overwrites cleared field');
    $assert($kirby->page('fabrics/located')->studioMapLocation()['lat'] === 30.0, 'Migration never overwrites existing Locator');

    $fields = Form::for($legacyPage)->fields()->toProps();
    $assert($fields['fabric_location']['type'] === 'locator', 'Locator field renders in Panel');
    $assert($fields['fabric_location']['saveZoom'] === true, 'Locator persists chosen zoom');
    $legacyPage = $legacyPage->update(['fabric_location' => Yaml::encode([])]);
    $assert($legacyPage->studioMapLocation() === null, 'Locator reset after migration hides map without resurrecting legacy values');

    echo "Maps: {$checks} checks passed using isolated content.\n";
} finally {
    Dir::remove($temporary);
}
