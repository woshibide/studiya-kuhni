# Media Kit downloads

Open Media Kit in Panel at `/panel/pages/mediakit`.
The «Страница» tab follows the website's section order: «Логотипы», «Миссия», «Ценности», and «Пресс-кит».
Each section has its own optional download list alongside its heading and text.

1. Upload files through the relevant section's file field, or select materials already uploaded to this page.
2. Open a file to edit «Название для посетителей» and the optional «Описание материала», then save the file.
3. Return to the page and drag the selected files into the desired order.
4. Save the page and open its preview to check the labels, order, and downloads.

An empty public label uses the filename.
Visitors see the label, optional description, format, size, and a «Скачать» link.
Format and size come from the file automatically.

The «Файлы» tab also lets you upload and manage materials before assigning them to sections.
Only saved selections appear in the visitor download lists; uploading a file alone does not add it to a section.
Removing a selection removes that download link while keeping the uploaded file.
Empty lists produce no download block.

Supported formats are PDF, ZIP, SVG, PNG, JPEG (`.jpg`, `.jpeg`, `.jpe`), and WebP.
Kirby checks both file extension and MIME type during upload and rejects unsafe SVG content.

Run `php tests/mediakit-downloads.php` to verify upload restrictions, metadata, selection order, and download links using isolated content.
