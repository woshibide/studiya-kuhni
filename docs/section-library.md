# Shared details and benefits

Open **Фабрики → Библиотека** in the Panel to manage shared names and icons.
**Для всего сайта** contains benefits available on any page, including kitchens.
**Только для кухонь** contains details and benefits offered only on individual kitchen pages.
The library starts with 14 shared benefits, 5 kitchen details, and 4 kitchen benefits.
Changes to a shared name or icon appear wherever that entry is selected.
Benefits never have a shared description.

On a kitchen, open **Блоки страницы**.
The details field uses Kirby's native searchable multiselect.
Benefit cards use Kirby's native structure table: add a row, choose a benefit, and enter text for this page.
Drag selected details or table rows to change their display order.
Up to five benefits can be selected on a page; duplicate benefits are rejected.
A benefit appears to visitors only when its own text on that page is filled.
Empty paragraphs, whitespace, and formatting without text keep it hidden.
The table previews the page-specific text using the existing rich text editor's native preview.

The **Открыть библиотеку** link opens the library separately.
Save library changes, then reopen the page editor to load updated choices after saving any unfinished page work.
The same benefits field is available on the other pages that already used benefit cards.
Kirby's normal save, preview, theme, dialog, and draft behavior applies.

Every library entry can be selected within its group.
To delete an entry, first remove it from page selections and save those pages.
Deleting an entry still used by a page or its saved changes is rejected with a message naming that page.
Missing or out-of-scope references remain identifiable in the editor and are omitted from visitor output.

## Blueprints and storage

The `studio/sections` plugin adds stable identities and scope filtering around native Kirby fields.
Its client code only aliases the native structure and multiselect components; it adds no custom controls or CSS.
The `fabrics` page owns three pools: `benefit_library`, `kitchen_feature_library`, and `kitchen_benefit_library`.
Each entry holds an immutable generated key, name, image UUID, and alternative text.
Page fields `kitchen_features` and `benefits_items` hold references in display order.
Benefit descriptions remain in each page's `benefits_items` rows.
Shared images belong to the site, so deleting the original kitchen does not remove library images.

Use `fields/section-selection` for kitchen details and reuse `fields/benefits` for benefit cards in other blueprints.
Scope is determined by the page template: `kuhnya` receives kitchen details and both benefit groups; other templates receive site-wide benefits.
When using a different field name, pass it to the options query, for example `page.studioFeatureOptions("details")`, and render with `$page->studioFeatures('details')`.
The equivalent benefit methods are `studioBenefitOptions` and `studioBenefits`.
Legacy rows remain readable until migrated.

## Migration and checks

```sh
php tools/migrate-section-library.php
php tools/migrate-section-library.php --apply
```

The default command reports a read-only migration plan.
`--apply` copies shared images, saves scoped pools on `fabrics`, and changes only section fields on affected pages.
It also upgrades the initial site-owned library without changing reference keys.
Original content files are backed up in a temporary directory; the command prints its path.
Original images remain in place.
Repeated runs do not duplicate migrated entries.
Pending Panel changes on affected pages or library settings stop migration before writes.

Panel plugin updates follow [the existing reload workflow](panel-updates.md).

```sh
php tests/section-library.php
php tests/panel-assets.php
composer test
```

Regression checks use isolated content copies and cover scope filtering, migration, drafts, backups, stable references, shared images, empty descriptions, limits, missing entries, and native form persistence.
