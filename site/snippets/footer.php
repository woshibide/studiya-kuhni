<?php
$needsMap = in_array($page->intendedTemplate()->name(), ['fabric', 'kuhnya'], true) && $page->studioMapLocation() !== null;
?>
<footer>
    
    <h3 id="footer-typed-line" aria-label="Кухни на заказ">
        <span class="footer-typed-prefix" aria-hidden="true">Кухни</span>
        <span id="footer-typed-word" aria-hidden="true"> на заказ</span>
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
            <?php
            $footerGroups = [
                [['contacts', 'Связь'], ['faq', 'FAQ']],
                [['fabrics/aran-cucine', 'Aran Cucine'], ['fabrics/aster-cucine', 'Aster Cucine'], ['fabrics/home-cucine', 'Home Cucine'], ['fabrics/mossman', 'Mossman'], ['fabrics/scavolini', 'Scavolini']],
                [['mediakit', 'Медиа Кит'], ['designers', 'Дизайнерам'], ['privacy', 'Конфиденциальность']],
            ];
            foreach ($footerGroups as $group):
                $links = [];
                foreach ($group as [$id, $label]) {
                    $target = page($id);
                    if ($target && $target->studioPubliclyVisible()) $links[] = [$target, $label];
                }
                if ($links === []) continue;
            ?>
                <ul>
                    <?php foreach ($links as [$target, $label]): ?>
                        <li><a class="hover-bg" href="<?= esc(relative_url($target->url()), 'attr') ?>"><?= esc($label) ?></a></li>
                    <?php endforeach ?>
                </ul>
            <?php endforeach ?>
        </div>
    </div>
    <a href="/" id="big-footer-text">
        Студия Кухни
    </a>
</footer>

<?php if ($kirby->option('debug')): ?>
<script src="<?= esc(studio_asset_url('assets/js/debug.js'), 'attr') ?>" defer></script>
<div id="debug-grid">
    <?php for($i = 0; $i < 24; $i++): ?>
        <div></div>
    <?php endfor; ?>
</div>
<?php endif ?>

<script src="<?= esc(studio_asset_url('assets/js/site-motion.js'), 'attr') ?>" defer></script>

<!-- embla carousel -->
<script src="<?= esc(studio_asset_url('assets/js/node_modules/embla-carousel/embla-carousel.umd.js'), 'attr') ?>" defer></script>
<script src="<?= esc(studio_asset_url('assets/js/node_modules/embla-carousel-autoplay/embla-carousel-autoplay.umd.js'), 'attr') ?>" defer></script>

<script src="<?= esc(studio_asset_url('assets/js/gsap-marquee.js'), 'attr') ?>" defer></script>
<script src="<?= esc(studio_asset_url('assets/js/brands.js'), 'attr') ?>" defer></script>
<script src="<?= esc(studio_asset_url('assets/js/gallery.js'), 'attr') ?>" defer></script>
<script src="<?= esc(studio_asset_url('assets/js/navbar-menus.js'), 'attr') ?>" defer></script>
<script src="<?= esc(studio_asset_url('assets/js/faq.js'), 'attr') ?>" defer></script>
<?php if ($needsMap): ?>
    <script src="<?= esc(studio_asset_url('assets/js/node_modules/leaflet/dist/leaflet.js'), 'attr') ?>" defer></script>
    <script src="<?= esc(studio_asset_url('assets/js/fabric-info-map.js'), 'attr') ?>" defer></script>
<?php endif ?>
<script src="<?= esc(studio_asset_url('assets/js/other-fabrics.js'), 'attr') ?>" defer></script>
<script src="<?= esc(studio_asset_url('assets/js/footer-parallax.js'), 'attr') ?>" defer></script>
<script src="<?= esc(studio_asset_url('assets/js/footer-typing.js'), 'attr') ?>" defer></script>
<script src="<?= esc(studio_asset_url('assets/js/mason-gallery.js'), 'attr') ?>" defer></script>

<?php
$template = $page->intendedTemplate()->name();
$jsFile = "assets/js/templates/{$template}.js";
$jsPath = kirby()->root('index') . '/' . $jsFile;

if (file_exists($jsPath)): ?>
    <script src="<?= esc(studio_asset_url($jsFile), 'attr') ?>" defer></script>
<?php endif ?>


<script src="<?= esc(studio_asset_url('assets/js/callback.js'), 'attr') ?>" defer></script>
<script src="<?= esc(studio_asset_url('assets/js/scroll-reveals.js'), 'attr') ?>" defer></script>
</body>
</html>
