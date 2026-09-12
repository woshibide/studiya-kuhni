# Rich text

The installed release is `medienbaecker/kirby-tiptap` 1.3.1.
Its field stores Tiptap JSON and exposes `tiptapText()` for frontend rendering.
Markdown storage and `tiptapTextInline()` described on the upstream main branch are not available in this release.

Shared `fields/rich-text` and `fields/rich-text-inline` blueprints use the `studio-tiptap` adapter.
The adapter extends the official PHP field and Panel component rather than copying or editing plugin code.
Existing plain text, Markdown, Kirbytext and Writer HTML are imported into the Panel response with their supported formatting.
Opening the Panel does not write content files.
An editor save uses the official JSON format.
Existing JSON passes through unchanged.

`Studio\RichText::panelValue()` imports through Kirbytext and the installed `Tiptap\Editor` parser.
Links use the released plugin's KirbyTag representation.
A JSON text node containing `(link: /contacts text: Контакты)` is a valid saved link without a ProseMirror link mark; the official renderer expands it into an HTML anchor.
Same-site absolute links become relative before saving, preventing local or staging domains from leaking into content.
Link titles, new-tab behavior and punctuation survive import.

Frontend templates use `$field->studioText()` for body prose and `$field->studioText(true)` inside existing paragraphs.
The optional second argument sets the minimum heading level, with H2 as the default, H3 below factory headings, and H4 below FAQ questions.
`$field->studioPlainText($limit)` provides decoded, Unicode-safe excerpts for cards, image descriptions and FAQ structured data.
The renderer clones fields before calling the official mutating `tiptapText()` method.
Legacy Kirbytext keeps its existing parsing path.

Rendered prose permits text formatting, safe links, lists, quotes and headings.
HTML attributes and active elements are filtered with Kirby's DOM sanitizer, and new-tab links receive `noopener noreferrer`.
Inline output flattens block elements while keeping readable line breaks.
Malformed or unsupported document nodes render empty instead of exposing JSON or selecting arbitrary snippets.
Any future Tiptap extensions must be added deliberately to the renderer's node allowlist and tested.

Narrative descriptions use the editor throughout heroes, CTAs, benefits, designer cards, factory copy, kitchen features, FAQ, general pages, privacy copy and article introductions.
Titles, short labels, prices, contact details, consent and SEO remain plain fields.
New article Tiptap blocks use the same renderer while existing article block types remain available.
New homepage, brands and designer introduction controls preserve the previous frontend copy until edited.

Run `php tests/rich-text.php` for legacy import, actual Kirby form submission, JSON rendering, safe inline structure, link portability, punctuation, FAQ schema and malformed content checks.
