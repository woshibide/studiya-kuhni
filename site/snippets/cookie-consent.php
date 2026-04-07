<?php if (!isset($_COOKIE['cookie_consent'])): ?>
<div id="cookie-banner">
    Мы используем файлы Cookie для улучшения работы сайта. 
    <div>
        <a href="<?= esc(relative_url('/privacy'), 'attr') ?>" class="cookie-banner-link">Подробнее</a>
        <button onclick="acceptCookies()" class="cookie-accept-btn">Принять</button>
    </div>
</div>
<script>
function acceptCookies() {
    const banner = document.getElementById('cookie-banner');
    banner.style.opacity = '0';
    setTimeout(() => {
        banner.style.display = 'none';
    }, 500);
    document.cookie = "cookie_consent=accepted; path=/; max-age=31536000"; // 1 year
}
</script>
<?php endif; ?>