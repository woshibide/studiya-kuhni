<footer>
    <div id="footer-details">
        <div class="info">
            <p>Студия Кухни, 2008 — 2026</p>
            <address>
                 Пятигорск, Ермолова 14, 357500
            </address>
            <p>
                Использование фотографий и материалов размещенных на сайте допускается исключительно с нашего письменного согласия.
            </p>
        </div>
        <div class="links">
            <ul>
            <?php
            $socialLinks = [
                ['title' => 'Связь', 'url' => page('contacts') ? page('contacts')->url() : '/contacts'],
                ['title' => 'FAQ', 'url' => page('faq') ? page('faq')->url() : '/faq'],
                ['title' => 'Портфолио', 'url' => page('portfolio') ? page('portfolio')->url() : '/portfolio'],
            ];
            foreach ($socialLinks as $socialLink): ?>
                <li>
                    <a href="<?= $socialLink['url'] ?>">
                        <?= $socialLink['title'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
            </ul>
            <ul>
            <?php
            $fabricsPage = page('fabrics');
            $fabrics = $fabricsPage ? $fabricsPage->children() : [];
            foreach ($fabrics as $fabric): ?>
                <li>
                    <a href="<?= $fabric->url() ?>">
                        <?= $fabric->title() ?>
                    </a>
                </li>
            <?php endforeach; ?>
            </ul>
            <ul>
            <?php
            $footerPages = [
                ['title' => 'Дизайнерам', 'url' => page('designers') ? page('designers')->url() : '/designers', 'external' => false],
                ['title' => 'Часть альянса Петру Групп', 'url' => 'https://mebelkmv.ru', 'external' => true],
                ['title' => 'Политика Конфиденциальности', 'url' => page('privacy') ? page('privacy')->url() : '/privacy', 'external' => false],
            ];
            foreach ($footerPages as $footerPage): ?>
                <li>
                    <a href="<?= $footerPage['url'] ?>"<?= $footerPage['external'] ? ' class="external-link" target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <?= $footerPage['title'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div id="big-footer-text">
        Студия Кухни
    </div>
</footer>

<!-- debug grid overlay -->
<script src="/assets/js/debug.js" defer></script>
<script src="/assets/js/smooth-scroll.js" defer></script>
<div id="debug-grid">
    <?php for($i = 0; $i < 24; $i++): ?>
        <div></div>
    <?php endfor; ?>
</div>

<!-- page transitions -->
<!-- <script src="https://cdn.jsdelivr.net/npm/swup@4.8.2/dist/Swup.umd.js" defer></script>
<script src="/assets/js/swup.js" defer></script> -->

<!-- embla carousel -->
<script src="/assets/js/node_modules/embla-carousel/embla-carousel.umd.js" defer></script>
<script src="/assets/js/node_modules/embla-carousel-autoplay/embla-carousel-autoplay.umd.js" defer></script>

<!-- gallery section -->
<script src="/assets/js/gallery.js" defer></script>
<script src="/assets/js/navbar-contact.js" defer></script>
<script src="/assets/js/faq.js" defer></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
<script src="/assets/js/fabric-info-map.js" defer></script>

<?php
$template = $page->intendedTemplate()->name();
$jsFile = "assets/js/templates/{$template}.js";
$jsPath = kirby()->root('index') . '/' . $jsFile;

if (file_exists($jsPath)): ?>
    <script src="<?= url($jsFile) ?>" defer></script>
<?php endif ?>


</body>
