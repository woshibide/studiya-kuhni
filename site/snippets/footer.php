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
            <div>
                <?php
                $socialMedias = ['Связь', 'FAQ', 'Портфолио'];
                foreach ($socialMedias as $social): ?>
                    <a>
                        <?php echo $social; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <ul>
            <?php
            $socialMedias = ['Instagram', 'Telegram', 'TikTok', 'Вконтакте', 'Петру Групп', 'Instagram', 'Telegram', 'TikTok', 'Вконтакте', 'Петру Групп'];
            foreach ($socialMedias as $social): ?>
                <li>
                    <a>
                        <?php echo $social; ?>
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
<div id="debug-grid">
    <?php for($i = 0; $i < 24; $i++): ?>
        <div></div>
    <?php endfor; ?>
</div>

<!-- page transitions -->
<script src="https://cdn.jsdelivr.net/npm/swup@4.8.2/dist/Swup.umd.js" defer></script>
<script src="/assets/js/swup.js" defer></script>

<!-- embla carousel -->
<script src="/assets/js/node_modules/embla-carousel/embla-carousel.umd.js" defer></script>
<script src="/assets/js/node_modules/embla-carousel-autoplay/embla-carousel-autoplay.umd.js" defer></script>

<!-- gallery section -->
<script src="/assets/js/gallery.js" defer></script>

</body>
