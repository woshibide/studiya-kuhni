<?php
    $hasNewArchive = page('archive') && page('archive')->children()->listed()->filterBy('is_new', true)->count() > 0;
?>

<footer>
    
    <h3 id="footer-typed-line">
        <span class="footer-typed-prefix">Кухни</span>
        <span id="footer-typed-word">на заказ</span>
    </h3>

    <div id="footer-details">
        <div class="info">
            <p><a class="hover-underline" href="<?= esc(relative_url('/'), 'attr') ?>">Студия Кухни</a>, 2008 — 2026</p>
            <p>Часть альянса <a class="hover-underline external-link" target="_blank" href="https://mebelkmv.ru">Петру Групп</a></p>
            <address>
                 Пятигорск, Ермолова 14, ТЦ «Palazzo», 357500
            </address>
            <p>
                Использование фотографий и материалов размещенных на сайте допускается исключительно с нашего письменного согласия.
            </p>
        </div>
        <div class="links">
            <ul>
                <li>
                    <a class="hover-bg" href="<?= esc(relative_url('/contacts'), 'attr') ?>">Связь</a>
                </li>
                <li>
                    <a class="hover-bg" href="<?= esc(relative_url('/faq'), 'attr') ?>">FAQ</a>
                </li>
                <li>
                    <?php if ($hasNewArchive): ?>
                        <span class="badge--new">new</span>
                    <?php endif ?>
                    <a class="hover-bg" href="<?= esc(relative_url('/archive'), 'attr') ?>">Воспоминания</a>
                </li>
            </ul>
            <ul>
                <li>
                    <a class="hover-bg" href="<?= esc(relative_url('/fabrics/aran-cucine'), 'attr') ?>">Aran Cucine</a>
                </li>
                <li>
                    <a class="hover-bg" href="<?= esc(relative_url('/fabrics/aster-cucine'), 'attr') ?>">Aster Cucine</a>
                </li>
                <li>
                    <a class="hover-bg" href="<?= esc(relative_url('/fabrics/home-cucine'), 'attr') ?>">Home Cucine</a>
                </li>
                <li>
                    <a class="hover-bg" href="<?= esc(relative_url('/fabrics/mossman'), 'attr') ?>">Mossman</a>
                </li>
                <li>
                    <a class="hover-bg" href="<?= esc(relative_url('/fabrics/scavolini'), 'attr') ?>">Scavolini</a>
                </li>
            </ul>
            <ul>
                <li>
                    <a class="hover-bg" href="<?= esc(relative_url('/mediakit'), 'attr') ?>">Медиа Кит</a>
                </li>
                <li>
                    <a class="hover-bg" href="<?= esc(relative_url('/designers'), 'attr') ?>">Дизайнерам</a>
                </li>
                <li>
                    <a class="hover-bg" href="<?= esc(relative_url('/privacy'), 'attr') ?>">Конфиденциальность</a>
                </li>
            </ul>
        </div>
    </div>
    <a href="/" id="big-footer-text">
        Студия Кухни
    </a>
</footer>

<!-- debug grid overlay -->
<script src="<?= esc(relative_url('assets/js/debug.js'), 'attr') ?>" defer></script>
<!-- <script src="<?= esc(relative_url('assets/js/smooth-scroll.js'), 'attr') ?>" defer></script> -->
<div id="debug-grid">
    <?php for($i = 0; $i < 24; $i++): ?>
        <div></div>
    <?php endfor; ?>
</div>

<!-- embla carousel -->
<script src="<?= esc(relative_url('assets/js/node_modules/embla-carousel/embla-carousel.umd.js'), 'attr') ?>" defer></script>
<script src="<?= esc(relative_url('assets/js/node_modules/embla-carousel-autoplay/embla-carousel-autoplay.umd.js'), 'attr') ?>" defer></script>

<script src="<?= esc(relative_url('assets/js/gallery.js'), 'attr') ?>" defer></script>
<script src="<?= esc(relative_url('assets/js/navbar-menus.js'), 'attr') ?>" defer></script>
<script src="<?= esc(relative_url('assets/js/faq.js'), 'attr') ?>" defer></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
<script src="<?= esc(relative_url('assets/js/fabric-info-map.js'), 'attr') ?>" defer></script>
<script src="<?= esc(relative_url('assets/js/other-fabrics.js'), 'attr') ?>" defer></script>
<script src="<?= esc(relative_url('assets/js/footer-typing.js'), 'attr') ?>" defer></script>

<?php
$template = $page->intendedTemplate()->name();
$jsFile = "assets/js/templates/{$template}.js";
$jsPath = kirby()->root('index') . '/' . $jsFile;

if (file_exists($jsPath)): ?>
    <script src="<?= esc(relative_url($jsFile), 'attr') ?>" defer></script>
<?php endif ?>


</body>
