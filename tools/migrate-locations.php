<?php

namespace Studio\Tools;

use Kirby\Cms\App;
use Kirby\Data\Yaml;
use Studio\Location;

require_once dirname(__DIR__) . '/kirby/bootstrap.php';
require_once dirname(__DIR__) . '/site/plugins/studio/Location.php';

function migrateLocations(App $kirby, bool $apply = false): array
{
    $results = [];
    foreach ($kirby->site()->index(true)->filterBy('intendedTemplate', 'fabric') as $page) {
        if ($page->content()->has('fabric_location')) {
            $results[] = ['page' => $page->id(), 'status' => 'skip-existing'];
            continue;
        }
        $location = Location::resolve(
            null,
            $page->fabric_map_lat()->value(),
            $page->fabric_map_lng()->value(),
            $page->fabric_map_zoom()->value(),
            ''
        );
        if ($location === null) {
            $results[] = ['page' => $page->id(), 'status' => 'skip-no-coordinates'];
            continue;
        }

        $value = ['lat' => $location['lat'], 'lon' => $location['lng'], 'zoom' => $location['zoom']];
        if ($apply) {
            // Schema-only save avoids materializing unrelated empty/default Panel fields.
            $kirby->impersonate('kirby', static fn () => $page->save(['fabric_location' => Yaml::encode($value)]));
        }
        $results[] = ['page' => $page->id(), 'status' => $apply ? 'migrated' : 'would-migrate', 'location' => $value];
    }
    return $results;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) return;
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$arguments = array_slice($argv, 1);
if ($arguments === ['--help']) {
    echo "Usage: php tools/migrate-locations.php [--apply]\n";
    echo "Default is read-only. --apply adds Locator values from valid legacy factory coordinates.\n";
    exit;
}
if ($arguments !== [] && $arguments !== ['--apply']) {
    fwrite(STDERR, "Usage: php tools/migrate-locations.php [--apply]\n");
    exit(1);
}

$kirby = new App(['roots' => ['index' => dirname(__DIR__)]]);
$results = migrateLocations($kirby, $arguments === ['--apply']);
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
