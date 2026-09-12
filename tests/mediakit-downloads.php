<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';

use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Files;
use Kirby\Data\Yaml;
use Kirby\Filesystem\Dir;
use Kirby\Form\Form;

$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/studio-mediakit-' . bin2hex(random_bytes(8));
mkdir($temporary . '/config', 0700, true);
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$parse = static function (string $html): DOMXPath {
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
    return new DOMXPath($document);
};

try {
    Dir::copy($root . '/content', $temporary . '/content');
    $kirby = new App([
        'roots' => [
            'index' => $root, 'config' => $temporary . '/config', 'content' => $temporary . '/content',
            'media' => $temporary . '/media', 'cache' => $temporary . '/cache', 'sessions' => $temporary . '/sessions',
        ],
        'urls' => ['index' => 'http://localhost:8000'],
        'options' => ['debug' => true, 'studio.environment' => 'local'],
    ]);
    $kirby->impersonate('kirby');
    $page = $kirby->site()->find('mediakit');
    $assert(str_starts_with(realpath($page->root()), realpath($temporary . '/content') . '/'), 'Uploads use isolated content only');

    $emptyFields = [];
    foreach (['logos', 'mission', 'values', 'press'] as $section) $emptyFields['mediakit_' . $section . '_files'] = '';
    $page = $page->update($emptyFields);
    $empty = $page->render();
    $assert(!str_contains($empty, '<ul class="mediakit-downloads"'), 'Empty download fields add no visitor lists');
    foreach (['Логотипы', 'Миссия', 'Ценности', 'Пресс Кит'] as $heading) {
        $assert(str_contains($empty, $heading), 'Existing section copy remains visible: ' . $heading);
    }

    $pdfSource = $temporary . '/studio-guidelines.pdf';
    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] /Contents 4 0 R >>',
        "<< /Length 0 >>\nstream\n\nendstream",
    ];
    $pdfBytes = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdfBytes);
        $pdfBytes .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref = strlen($pdfBytes);
    $pdfBytes .= "xref\n0 5\n0000000000 65535 f \n";
    foreach (array_slice($offsets, 1) as $offset) $pdfBytes .= sprintf("%010d 00000 n \n", $offset);
    $pdfBytes .= "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    file_put_contents($pdfSource, $pdfBytes);

    $zipSource = $temporary . '/studio-assets.zip';
    $entryName = 'readme.txt';
    $entryData = "Studio media assets\n";
    $entryCrc = crc32($entryData);
    $entrySize = strlen($entryData);
    $zipBytes = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $entryCrc, $entrySize, $entrySize, strlen($entryName), 0) . $entryName . $entryData;
    $centralOffset = strlen($zipBytes);
    $central = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $entryCrc, $entrySize, $entrySize, strlen($entryName), 0, 0, 0, 0, 0, 0) . $entryName;
    $zipBytes .= $central . pack('VvvvvVVv', 0x06054b50, 0, 0, 1, 1, strlen($central), $centralOffset, 0);
    file_put_contents($zipSource, $zipBytes);

    $svgSource = $temporary . '/studio-logo.svg';
    file_put_contents($svgSource, '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="40"><rect width="120" height="40" fill="black"/></svg>');
    $create = static fn (string $source, array $content = []): File => File::create([
        'source' => $source, 'parent' => $page, 'template' => 'mediakit-download', 'content' => $content,
    ]);
    $pdf = $create($pdfSource, ['download_label' => 'Пресс-кит & <b>студия</b>', 'download_description' => 'Описание <script>alert(1)</script>']);
    $zip = $create($zipSource);
    $svg = $create($svgSource, ['download_label' => 'Логотип в векторе']);
    $png = $create($root . '/assets/icons/favicons/favicon32px.png', ['download_label' => 'Логотип PNG']);
    $assert($pdf->mime() === 'application/pdf' && $zip->mime() === 'application/zip', 'Native upload accepts actual PDF and ZIP bytes');
    $assert($svg->mime() === 'image/svg+xml' && $png->mime() === 'image/png', 'Native upload accepts clean SVG and PNG files');

    $rules = $pdf->blueprint()->accept();
    $browserExtensions = explode(',', $pdf->blueprint()->acceptAttribute());
    foreach (['pdf', 'zip', 'svg', 'png', 'jpg', 'jpeg', 'jpe', 'webp'] as $extension) {
        $assert(in_array($extension, $rules['extension'], true) && in_array('.' . $extension, $browserExtensions, true), $extension . ' is accepted by both browser picker and server');
    }
    foreach (['php', 'phar', 'exe', 'html', 'js'] as $extension) {
        $assert(!in_array($extension, $rules['extension'], true) && !in_array('.' . $extension, $browserExtensions, true), $extension . ' is excluded from downloads');
    }
    $assert(in_array('application/zip', $rules['mime'], true) && in_array('application/pdf', $rules['mime'], true), 'Server MIME allowlist includes document and archive types');
    $assert(!in_array('application/octet-stream', $rules['mime'], true), 'Unknown binary files are not accepted');

    foreach ([
        'rejected.php' => '<?php echo "unsafe";',
        'rejected.html' => '<html><script>unsafe()</script></html>',
        'disguised.pdf' => '<html><script>unsafe()</script></html>',
        'active.svg' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
    ] as $filename => $bytes) {
        $source = $temporary . '/' . $filename;
        file_put_contents($source, $bytes);
        $rejected = false;
        try {
            $create($source);
        } catch (Kirby\Exception\Exception) {
            $rejected = true;
        }
        $assert($rejected && !is_file($page->root() . '/' . $filename), 'Native upload rejects ' . $filename . ' without publishing it');
    }

    $page = $page->update([
        'mediakit_logos_files' => Yaml::encode([$svg->uuid()->toString(), $png->uuid()->toString()]),
        'mediakit_press_files' => Yaml::encode([$zip->uuid()->toString(), $pdf->uuid()->toString()]),
    ]);
    $beforeRender = $page->content()->toArray();
    $html = $page->render();
    $dom = $parse($html);
    $assert($beforeRender === $page->content()->toArray(), 'Rendering cannot change editorial source fields');
    $assert($dom->query('//ul[@class="mediakit-downloads"]')->length === 2, 'Only populated sections display download lists');
    $pressLinks = $dom->query('//section[@aria-labelledby="mediakit-press"]//a[@download]');
    $assert($pressLinks->length === 2, 'Press section displays both selected files');
    $assert($pressLinks->item(0)->getAttribute('href') === $zip->url() && $pressLinks->item(1)->getAttribute('href') === $pdf->url(), 'Selected order controls visitor download order');
    $assert($pressLinks->item(0)->getAttribute('download') === $zip->filename(), 'Download attribute uses safe native filename');
    $assert(str_contains($pressLinks->item(0)->textContent, $zip->filename()), 'Empty public label falls back to filename');
    $assert(str_contains($pressLinks->item(0)->textContent, 'ZIP') && str_contains($pressLinks->item(0)->textContent, $zip->niceSize()), 'Visitor sees actual file format and size');
    $assert($dom->query('//a[@download]//script | //a[@download]//b')->length === 0, 'File labels and descriptions cannot create active or formatting elements');
    $assert(str_contains($pressLinks->item(1)->textContent, 'Пресс-кит & <b>студия</b>') && str_contains($pressLinks->item(1)->textContent, '<script>alert(1)</script>'), 'Public metadata is rendered as escaped text');

    $pageForm = Form::for($page);
    $form = $pageForm->fields()->toProps();
    foreach (array_keys($emptyFields) as $field) {
        $assert($form[$field]['uploads']['template'] === 'mediakit-download' && in_array('.zip', explode(',', $form[$field]['uploads']['accept']), true), $field . ' native upload dialog allows ZIP');
        $assert($page->query($page->blueprint()->field($field)['query'])->count() === 4, $field . ' picker lists uploaded Media Kit materials');
    }
    $fileForm = Form::for($pdf)->fields()->toProps();
    $assert(isset($fileForm['download_label'], $fileForm['download_description'], $fileForm['alt']), 'Uploaded materials expose public metadata and image description');
    $assert(str_contains($pageForm->values()['mediakit_press_files'][0]['info'], 'zip'), 'Native selected-file preview shows a readable format');

    $outside = File::create(['source' => $pdfSource, 'parent' => $kirby->site()->find('privacy'), 'template' => 'mediakit-download']);
    $manual = new File(['filename' => 'manual.html', 'parent' => $page, 'template' => 'mediakit-download']);
    file_put_contents($manual->root(), '<html>Invalid material</html>');
    $filtered = snippet('mediakit-downloads', ['files' => new Files([$outside, $manual, $zip]), 'sourcePage' => $page], true);
    $filteredLinks = $parse($filtered)->query('//a[@download]');
    $assert($filteredLinks->length === 1 && $filteredLinks->item(0)->getAttribute('href') === $zip->url(), 'Renderer excludes another page\'s files and files outside the allowlist');

    $pdf->delete();
    $page = $page->clone();
    $remaining = snippet('mediakit-downloads', ['files' => $page->mediakit_press_files()->toFiles(), 'sourcePage' => $page], true);
    $assert($parse($remaining)->query('//a[@download]')->length === 1, 'Deleted file references disappear without breaking remaining downloads');
    $assert(trim(snippet('mediakit-downloads', ['files' => new Files([]), 'sourcePage' => $page], true)) === '', 'Empty collections render no empty markup');
} finally {
    if (isset($kirby)) $kirby->session()->destroy();
    Dir::remove($temporary);
}

echo "Media Kit downloads: {$checks} checks passed using isolated content.\n";
