<?php

declare(strict_types=1);

namespace Studio;

use DOMElement;
use Kirby\Content\Field;
use Kirby\Toolkit\Dom;
use Tiptap\Editor;

/** Renders existing Kirbytext and the released Tiptap JSON format without changing content. */
final class RichText
{
    public static function render(Field $field, bool $inline = false, int $headingLevel = 2): string
    {
        $raw = trim((string)$field->value());
        if ($raw === '') return '';

        $decoded = json_decode($raw, true);
        $isDocument = is_array($decoded) && ($decoded['type'] ?? null) === 'doc';
        if ($isDocument || preg_match('/^\s*\{\s*"(?:type|content|inline)"\s*:/', $raw)) {
            if (!$isDocument || !is_array($decoded['content'] ?? null) || !self::validNodes($decoded['content'])) return '';
            // The official field method mutates its input, so always render a clone.
            $html = (string)(clone $field)->tiptapText(['allowHtml' => false]);
        } else {
            $html = (string)(clone $field)->kirbytext();
        }

        $html = self::sanitize($html, $inline, $headingLevel);
        if ($html === '') return '';
        $tag = $inline ? 'span' : 'div';
        $class = $inline ? 'studio-rich-text-inline' : 'studio-rich-text';
        return '<' . $tag . ' class="' . $class . '">' . $html . '</' . $tag . '>';
    }

    public static function plain(Field $field, int $limit = 0): string
    {
        $html = self::render($field);
        $html = preg_replace('~<(?:br\b[^>]*|/(?:p|div|li|h[1-6]|blockquote))>~i', ' ', $html);
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        return $limit > 0 && mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit)) . '...' : $text;
    }

    private static function validNodes(array $nodes, int $depth = 0): bool
    {
        if ($depth > 64 || !array_is_list($nodes)) return false;
        $types = ['text', 'paragraph', 'hardBreak', 'heading', 'bulletList', 'orderedList', 'listItem', 'blockquote', 'codeBlock', 'horizontalRule', 'taskList', 'taskItem', 'kirbyTag'];
        foreach ($nodes as $node) {
            if (!is_array($node) || !in_array($node['type'] ?? null, $types, true)) return false;
            if (isset($node['attrs']) && !is_array($node['attrs'])) return false;
            if (isset($node['content']) && (!is_array($node['content']) || !self::validNodes($node['content'], $depth + 1))) return false;
            if ($node['type'] === 'text' && !is_string($node['text'] ?? null)) return false;
            if ($node['type'] === 'kirbyTag' && !is_string($node['attrs']['content'] ?? null)) return false;
            if (isset($node['marks'])) {
                if (!is_array($node['marks']) || !array_is_list($node['marks'])) return false;
                foreach ($node['marks'] as $mark) {
                    if (!is_array($mark) || !in_array($mark['type'] ?? null, ['bold', 'italic', 'strike', 'code'], true)) return false;
                }
            }
        }
        return true;
    }

    /** Import legacy formatting into the editor response; no files or models are saved. */
    public static function panelValue(string $value, object $parent, bool $inline = false): string
    {
        if (trim($value) === '') return '';
        $decoded = json_decode($value, true);
        if (is_array($decoded) && ($decoded['type'] ?? null) === 'doc') return $value;

        $html = self::render(new Field($parent, 'text', $value), $inline, 1);
        if ($html === '') return '';
        $dom = new Dom($html);
        // Released Tiptap stores links as KirbyTags, not ProseMirror link marks.
        // Mirror its official paste transform so imported links survive an editor save.
        foreach ($dom->query('//a[@href]') as $anchor) {
            $href = $anchor->getAttribute('href');
            $siteUrl = rtrim($parent->kirby()->url('index'), '/');
            if ($href === $siteUrl || str_starts_with($href, $siteUrl . '/') || str_starts_with($href, $siteUrl . '#') || str_starts_with($href, $siteUrl . '?')) {
                $href = substr($href, strlen($siteUrl)) ?: '/';
                if ($href[0] !== '/') $href = '/' . $href;
            }
            $text = $anchor->textContent;
            $tag = '(link: ' . str_replace(['(', ')', ' '], ['%28', '%29', '%20'], $href);
            if ($text !== '' && $text !== $href) {
                $tag .= ' text: ' . self::tagAttribute($text);
            }
            if ($anchor->getAttribute('target') === '_blank') $tag .= ' target: _blank';
            if ($anchor->hasAttribute('title')) $tag .= ' title: ' . self::tagAttribute($anchor->getAttribute('title'));
            $tag .= ')';
            $anchor->parentNode->replaceChild($dom->document()->createTextNode($tag), $anchor);
        }
        $editorHtml = $dom->toString();
        $document = (new Editor())->setContent($inline ? '<p>' . $editorHtml . '</p>' : $editorHtml)->getDocument();
        $document['inline'] = $inline;
        return json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private static function sanitize(string $html, bool $inline, int $headingLevel): string
    {
        if (trim($html) === '') return '';
        $dom = new Dom($html);
        // KirbyTags escape text again. Decode only delimiter punctuation in DOM
        // text/attributes, never HTML markup, so imported link labels roundtrip.
        foreach ($dom->query('//a//text()') as $text) {
            $text->nodeValue = str_replace(['&#40;', '&#41;', '&#58;'], ['(', ')', ':'], $text->nodeValue);
        }
        foreach ($dom->query('//a[@title]') as $anchor) {
            $anchor->setAttribute('title', str_replace(['&#40;', '&#41;', '&#58;'], ['(', ')', ':'], $anchor->getAttribute('title')));
        }
        $tags = array_fill_keys(['html', 'body', 'div', 'span', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'code', 'pre', 'blockquote', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr'], []);
        $tags['a'] = ['href', 'title', 'target', 'rel'];
        $tags['ol'] = ['start'];
        $dom->sanitize([
            'allowedTags' => $tags,
            'allowedAttrs' => [],
            'allowedDataUris' => [],
            'allowedNamespaces' => [],
            'allowedPIs' => [],
            'disallowedTags' => ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form', 'input', 'button', 'textarea', 'select'],
            'elementCallback' => static function (DOMElement $element): void {
                if ($element->tagName !== 'a') return;
                if ($element->getAttribute('target') === '_blank') {
                    $element->setAttribute('rel', 'noopener noreferrer');
                } else {
                    $element->removeAttribute('target');
                    $element->removeAttribute('rel');
                }
            },
        ]);

        $headings = $dom->query('//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]');
        $lowest = 6;
        foreach ($headings as $heading) $lowest = min($lowest, (int)substr($heading->nodeName, 1));
        $offset = max(0, min(6, $headingLevel) - $lowest);
        foreach ($headings as $heading) {
            $replacement = $dom->document()->createElement('h' . min(6, (int)substr($heading->nodeName, 1) + $offset));
            foreach (iterator_to_array($heading->childNodes) as $child) $replacement->appendChild($child);
            $heading->parentNode->replaceChild($replacement, $heading);
        }

        if ($inline) {
            foreach ($dom->query('//*[self::div or self::p or self::li or self::ul or self::ol or self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6 or self::blockquote or self::pre or self::hr]') as $block) {
                if (!in_array($block->nodeName, ['ul', 'ol'], true)) $block->appendChild($dom->document()->createElement('br'));
                // Kirby Dom::unwrap intentionally discards direct text nodes.
                // Moving all children retains prose while removing block semantics.
                foreach (iterator_to_array($block->childNodes) as $child) $block->parentNode->insertBefore($child, $block);
                $block->parentNode->removeChild($block);
            }
        }
        return trim(preg_replace('~(?:\s*<br\s*/?>\s*)+$~i', '', $dom->toString()));
    }

    private static function tagAttribute(string $value): string
    {
        return str_replace(['(', ')', ':'], ['&#40;', '&#41;', '&#58;'], $value);
    }
}
