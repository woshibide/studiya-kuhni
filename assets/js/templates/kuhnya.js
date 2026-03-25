const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

const initKunyaHeroEffects = () => {
    const hero = document.querySelector('[data-kunya-hero]');
    const media = document.querySelector('[data-kunya-media]');
    const image = document.querySelector('[data-kunya-media-image]');

    if (!hero || !media || !image) {
        return;
    }

    let ticking = false;

    const update = () => {
        const mediaRect = media.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        // start at 0 when media enters viewport and finish at 1 when it leaves.
        const progress = clamp((viewportHeight - mediaRect.top) / (viewportHeight + mediaRect.height), 0, 1);

        // tiny parallax shift and zoom-in to keep movement subtle.
        const parallaxY = (progress - 0.5) * 80;
        const scale = 1.02 + progress * 0.05;

        image.style.transform = `translate3d(0, ${parallaxY}px, 0) scale(${scale})`;
        ticking = false;
    };

    const onScroll = () => {
        if (ticking) {
            return;
        }

        ticking = true;
        requestAnimationFrame(update);
    };

    update();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initKunyaHeroEffects();
    });
} else {
    initKunyaHeroEffects();
}
