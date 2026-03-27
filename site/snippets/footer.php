<footer>
    
    <h3 id="footer-typed-line">
        <span class="footer-typed-prefix">Кухни</span>
        <span id="footer-typed-word">на заказ</span>
    </h3>

    <div id="footer-details">
        <div class="info">
            <p><a href="/">Студия Кухни</a>, 2008 — 2026</p>
            <p>Часть альянса <a class="external-link" target="_blank" href="https://mebelkmv.ru">Петру Групп</a></p>
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
                    <a href="/contacts">Связь</a>
                </li>
                <li>
                    <a href="/faq">FAQ</a>
                </li>
                <li>
                    <a href="/archive">Архив</a>
                </li>
            </ul>
            <ul>
                <li>
                    <a href="/fabrics/aran-cucine">Aran Cucine</a>
                </li>
                <li>
                    <a href="/fabrics/aster-cucine">Aster Cucine</a>
                </li>
                <li>
                    <a href="/fabrics/home-cucine">Home Cucine</a>
                </li>
                <li>
                    <a href="/fabrics/mossman">Mossman</a>
                </li>
                <li>
                    <a href="/fabrics/scavolini">Scavolini</a>
                </li>
            </ul>
            <ul>
                <li>
                    <a href="/designers">Дизайнерам</a>
                </li>
                <li>
                    <a href="/privacy">Политика Конфиденциальности</a>
                </li>
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
<script src="/assets/js/other-fabrics.js" defer></script>
<script src="/assets/js/footer-typing.js" defer></script>

<?php
$template = $page->intendedTemplate()->name();
$jsFile = "assets/js/templates/{$template}.js";
$jsPath = kirby()->root('index') . '/' . $jsFile;

if (file_exists($jsPath)): ?>
    <script src="<?= url($jsFile) ?>" defer></script>
<?php endif ?>


</body>
