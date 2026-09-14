<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Content\Field;
use Kirby\Data\Yaml;
use Kirby\Filesystem\Dir;
use Kirby\Form\Form;
use Studio\RichText;

function relative_url(string $path): string
{
    return $path;
}

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/studio-rich-text-' . bin2hex(random_bytes(6));
mkdir($temporary . '/config', 0700, true);
try {
    $kirby = new App([
        'roots' => [
            'index' => $root, 'config' => $temporary . '/config', 'content' => $temporary . '/content',
            'media' => $temporary . '/media', 'cache' => $temporary . '/cache', 'sessions' => $temporary . '/sessions',
        ],
        'urls' => ['index' => 'https://studio.example.com'],
        'options' => ['debug' => true, 'studio.environment' => 'local'],
    ]);
    $kirby->impersonate('kirby');
    $kirby->site()->update(['title' => 'Fixture site']);
    $page = Page::factory(['slug' => 'text-fixture', 'template' => 'default', 'content' => ['title' => 'Fixture']]);
    $field = static fn (string $value): Field => new Field($page, 'text', $value);
    $document = static fn (array $content, bool $inline = false): string => json_encode(['type' => 'doc', 'content' => $content, 'inline' => $inline], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $paragraph = static fn (string $text, array $marks = []): array => ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text, 'marks' => $marks]]];

    $legacy = "# Заголовок\n\nТекст **жирный** и *курсив*.\n\n- Первый\n- Второй\n\n(link: https://example.com/details text: Подробнее)";
    $text = $field($legacy);
    $html = $text->studioText();
    $assert(str_contains($html, '<h2>Заголовок</h2>') && !str_contains($html, '<h1'), 'Body heading starts at H2');
    $assert(str_contains($html, '<strong>жирный</strong>') && str_contains($html, '<em>курсив</em>'), 'Legacy Markdown formatting preserved');
    $assert(str_contains($html, '<ul>') && str_contains($html, '<li>Первый</li>'), 'Legacy lists preserved');
    $assert(str_contains($html, 'href="https://example.com/details"'), 'Legacy KirbyTag link preserved');
    $assert($text->value() === $legacy, 'Rendering does not mutate source field');
    $assert($field('Кухня &amp; дом')->studioPlainText() === 'Кухня & дом', 'Plain extraction decodes entities');
    $assert($field('Кухня мечты')->studioPlainText(5) === 'Кухня...', 'Excerpt truncates decoded Unicode text');

    $json = $document([$paragraph('Новая кухня', [['type' => 'bold']]), $paragraph('(link: https://example.com text: Фабрика)')]);
    $jsonField = $field($json);
    $jsonHtml = $jsonField->studioText();
    $assert(str_contains($jsonHtml, '<strong>Новая кухня</strong>'), 'Official Tiptap renderer outputs new bold text');
    $assert(str_contains($jsonHtml, 'href="https://example.com"'), 'Official Tiptap link format renders');
    $assert($jsonField->value() === $json, 'Official mutating method cannot corrupt stored JSON');
    $assert($jsonField->studioPlainText() === 'Новая кухня Фабрика', 'JSON excerpts contain visible text only');
    $inline = $jsonField->studioText(true);
    $assert(!preg_match('~<(?:p|div|h[1-6]|ul|ol|li)\b~', $inline), 'Inline output cannot nest blocks inside paragraphs');
    $assert(str_contains($inline, '<br>'), 'Inline output preserves paragraph boundaries');
    $assert($field($document([]))->studioText() === '', 'Empty editor document produces no wrapper');
    $assert($field('{"type":"doc","content":')->studioText() === '', 'Malformed document never exposes raw JSON');
    $assert($field('{"type":"doc","content":"bad"}')->studioText() === '', 'Invalid document never exposes raw JSON');
    $assert($field($document([['type' => '../../header']]))->studioText() === '', 'Unknown node cannot select arbitrary snippets');
    $assert($field($document([['type' => 'paragraph', 'content' => ['invalid']]]))->studioText() === '', 'Malformed nested content cannot cause a renderer error');
    $assert($field($document([['type' => 'text', 'text' => ['invalid']]]))->studioText() === '', 'Malformed text cannot cause a renderer error');
    $assert($field($document([$paragraph('Safe', [['type' => '../../header']])]))->studioText() === '', 'Unknown marks cannot select arbitrary snippets');

    $unsafe = '<p onclick="alert(1)"><strong>Safe</strong><script>alert(1)</script><iframe src="https://example.com"></iframe><a href="javascript:alert(1)">Bad</a><a href="https://example.com" target="_blank" onclick="bad()">Good</a></p>';
    $safe = $field($unsafe)->studioText();
    $assert(!str_contains($safe, '<script') && !str_contains($safe, '<iframe') && !str_contains($safe, 'onclick'), 'Raw legacy HTML cannot introduce active elements or handlers');
    $assert(!str_contains($safe, 'javascript:') && str_contains($safe, 'rel="noopener noreferrer"'), 'Links use safe schemes and isolated new tabs');
    $assert(str_contains($field($document([$paragraph('<img src=x onerror=alert(1)>')]))->studioText(), '&lt;img'), 'Raw HTML typed in Tiptap stays literal text');

    $panelJson = RichText::panelValue($legacy, $page);
    $panelDoc = json_decode($panelJson, true, flags: JSON_THROW_ON_ERROR);
    $assert($panelDoc['type'] === 'doc', 'Panel imports legacy formatting as editor JSON');
    $roundtrip = $field($panelJson)->studioText();
    foreach (['<h2>Заголовок</h2>', '<strong>жирный</strong>', '<em>курсив</em>', '<ul>', 'href="https://example.com/details"'] as $expected) {
        $assert(str_contains($roundtrip, $expected), 'Legacy edit roundtrip preserves ' . $expected);
    }
    $assert(RichText::panelValue($json, $page) === $json, 'Existing editor JSON passes through byte for byte');
    $assert(str_contains($field(RichText::panelValue("Один\nДва", $page, true))->studioText(true), 'Один'), 'Inline Panel import stays readable');
    $internalJson = RichText::panelValue('(link: /contacts text: Контакты)', $page);
    $assert(str_contains($internalJson, '(link: /contacts text: Контакты)'), 'Panel import keeps internal links portable across local, staging and production hosts');
    $assert(!str_contains($internalJson, 'studio.example.com'), 'Panel import never persists current origin');
    foreach (['Контакты (звонок)', 'Звонок)', 'Контакты target: _blank'] as $label) {
        $oldLink = '<a href="/contacts" target="_blank" title="Контакты (звонок): детали">' . $label . '</a>';
        $linkHtml = $field(RichText::panelValue($oldLink, $page))->studioText();
        $assert(str_contains($linkHtml, '>' . $label . '</a>'), 'Link label punctuation survives editor save: ' . $label);
        $assert(str_contains($linkHtml, 'target="_blank"') && str_contains($linkHtml, 'title="Контакты (звонок): детали"'), 'Existing link target and title survive editor save');
    }

    $form = new Form(fields: ['text' => ['type' => 'studio-tiptap', 'buttons' => ['bold', 'italic', 'link'], 'inline' => false]], model: $page);
    $form->fill(['text' => $legacy]);
    $editorValue = $form->toFormValues()['text'];
    $assert(json_decode($editorValue, true)['type'] === 'doc', 'Actual Kirby field exposes imported JSON to Panel');
    $assert($page->text()->isEmpty(), 'Panel import never updates content model');
    $form->submit(['text' => $json]);
    $assert($form->isValid(), 'Official Tiptap validations accept editor JSON');
    $assert($form->toStoredValues()['text'] === $json, 'Submitted editor JSON survives actual Kirby form storage');
    $form->submit(['text' => $internalJson]);
    $storedPage = Page::factory(['slug' => 'saved-link-fixture', 'template' => 'default', 'content' => ['text' => $form->toStoredValues()['text']]]);
    $storedHtml = snippet('article-text', ['text' => $storedPage->text()], true);
    $storedDom = new DOMDocument();
    @$storedDom->loadHTML('<?xml encoding="UTF-8">' . $storedHtml);
    $storedXPath = new DOMXPath($storedDom);
    $assert($storedXPath->query('//a[text()="Контакты"]')->length === 1, 'Saved unmarked KirbyTag text node renders as a real link');
    $assert($storedXPath->evaluate('string(//a[text()="Контакты"]/@href)') === 'https://studio.example.com/contacts', 'Saved internal link resolves against the current website origin');
    $assert(!str_contains($storedDom->textContent, '(link:'), 'Visitors never see stored link syntax in article prose');

    $questionJson = Yaml::encode([['question' => 'Вопрос </script><script>bad()</script>', 'answer' => $json, 'category' => 'general']]);
    $faqPage = Page::factory(['slug' => 'faq-fixture', 'template' => 'faq', 'content' => ['title' => 'FAQ', 'questions' => $questionJson]]);
    $faq = snippet('faq-section', ['items' => $faqPage->questions()->toStructure()], true);
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $faq);
    $xpath = new DOMXPath($dom);
    $assert($xpath->query('//script')->length === 1, 'FAQ question cannot escape structured-data script');
    $schema = json_decode($xpath->evaluate('string(//script[@type="application/ld+json"])'), true, flags: JSON_THROW_ON_ERROR);
    $assert($schema['mainEntity'][0]['acceptedAnswer']['text'] === 'Новая кухня Фабрика', 'FAQ schema uses rendered plain answer');
    $assert($xpath->query('//div[contains(@class,"faq-answer-content")]//strong')->length === 1, 'Nested structure answer renders Tiptap formatting');

    $block = new Kirby\Cms\Block(['type' => 'tiptap', 'content' => ['text' => $json]]);
    $assert(str_contains($block->toHtml(), '<strong>Новая кухня</strong>'), 'New article Tiptap block uses compatible safe renderer');

    foreach ([
        'simple-hero' => ['simple_hero_description' => $json],
        'cta' => ['cta_text' => $json],
        'cta-warmup' => ['cta_warmup_text' => $json],
        'brands' => ['brands_intro_text' => $json],
        'benefits' => ['benefits_items' => Yaml::encode([['title' => 'Benefit', 'text' => $json]])],
        'fabric-info' => ['fabric_info_text' => $json],
    ] as $snippetName => $content) {
        $fixture = Page::factory(['slug' => 'consumer-fixture', 'template' => 'home', 'content' => ['title' => 'Fixture', ...$content]]);
        $result = snippet($snippetName, ['page' => $fixture], true);
        $assert(str_contains($result, '<strong>Новая кухня</strong>'), $snippetName . ' renders rich field formatting');
        $assert(!str_contains($result, '&quot;type&quot;') && !str_contains($result, '"type":"doc"'), $snippetName . ' never prints editor JSON');
    }

    $cardPage = Page::factory(['slug' => 'card-fixture', 'template' => 'kuhnya', 'content' => ['title' => 'Fixture', 'intro' => $json]]);
    $card = snippet('kuhnya-card-overview', ['kuhnya' => $cardPage], true);
    $assert(str_contains($card, 'Новая кухня Фабрика') && !str_contains($card, '"type":"doc"'), 'Kitchen card decodes JSON before excerpting');

    $brandPage = Page::create(['slug' => 'brand-fixture', 'template' => 'home', 'content' => ['title' => 'Brands', 'brands_heading' => 'Наши фабрики']]);
    $accessibleBrandCount = static function (string $html): int {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        return (new DOMXPath($dom))->query('//div[contains(concat(" ", normalize-space(@class), " "), " brand-item ")][not(ancestor::*[@aria-hidden="true"])]')->length;
    };
    $brandHtml = snippet('brands', ['page' => $brandPage], true);
    $assert($accessibleBrandCount($brandHtml) === 13, 'Empty custom brand list retains all existing logos without accessible duplicates');
    $assert(str_contains($brandHtml, '<h2>Наши фабрики</h2>'), 'Brand heading uses its Panel field');
    $logo = Kirby\Cms\File::create(['source' => $root . '/assets/icons/favicons/favicon32px.png', 'parent' => $brandPage, 'template' => 'image']);
    $fileLinkJson = $document([$paragraph('(link: ' . $logo->uuid()->toString() . ' text: Логотип)')]);
    $form->submit(['text' => $fileLinkJson]);
    $savedFileLink = $form->toStoredValues()['text'];
    $fileLinkHtml = $field($savedFileLink)->studioText();
    $fileDom = new DOMDocument();
    @$fileDom->loadHTML('<?xml encoding="UTF-8">' . $fileLinkHtml);
    $fileXPath = new DOMXPath($fileDom);
    $assert($fileXPath->evaluate('string(//a[text()="Логотип"]/@href)') === $logo->url(), 'Saved file UUID link resolves to the uploaded file media URL');
    $assert(!str_contains($fileLinkHtml, 'file://'), 'Visitors never receive an unresolved file UUID link');
    $assert($field($savedFileLink)->studioPlainText() === 'Логотип', 'File UUID link stays readable in excerpts');
    $brandPage = $brandPage->update(['brands_items' => Yaml::encode([
        ['name' => 'Custom <script>unsafe()</script>', 'logo' => $logo->uuid()->toString()],
        ['name' => 'Missing logo', 'logo' => 'missing.png'],
    ])]);
    $brandHtml = snippet('brands', ['page' => $brandPage], true);
    $assert($accessibleBrandCount($brandHtml) === 1, 'Configured brand list replaces fallback and skips invalid rows');
    $assert(str_contains($brandHtml, 'Custom &lt;script&gt;unsafe()&lt;/script&gt;') && !str_contains($brandHtml, '<script>unsafe()'), 'Custom brand names are escaped');
    $brandPage = $brandPage->update(['brands_items' => Yaml::encode([['name' => 'Missing logo', 'logo' => 'missing.png']])]);
    $brandHtml = snippet('brands', ['page' => $brandPage], true);
    $assert(!str_contains($brandHtml, 'class="brand-item '), 'Invalid configured list never silently restores unrelated logos');

    foreach ([
        'designers' => ['designers_work'],
        'mediakit' => ['mediakit_logos', 'mediakit_mission', 'mediakit_values', 'mediakit_press'],
    ] as $template => $sections) {
        $content = ['title' => 'Fixture'];
        foreach ($sections as $prefix) {
            $content[$prefix . '_heading'] = 'Heading ' . $prefix;
            $content[$prefix . '_text'] = $json;
        }
        $fixture = Page::factory(['slug' => 'section-fixture-' . $template, 'template' => $template, 'content' => $content]);
        $result = $fixture->render();
        $sectionDom = new DOMDocument();
        @$sectionDom->loadHTML('<?xml encoding="UTF-8">' . $result);
        $sectionXPath = new DOMXPath($sectionDom);
        foreach ($sections as $prefix) {
            $assert($sectionXPath->query('//h2[normalize-space(.)="Heading ' . $prefix . '"]')->length === 1, $prefix . ' heading uses Panel value');
        }
        $assert(substr_count($result, '<strong>Новая кухня</strong>') === count($sections), $template . ' body fields render formatting in every section');
    }
} finally {
    if (isset($kirby)) $kirby->session()->destroy();
    Dir::remove($temporary);
}
echo "Rich text: {$checks} checks passed.\n";
