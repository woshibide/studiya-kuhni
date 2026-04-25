const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

const KUNYA_HERO_SELECTOR = '[data-kunya-hero]';
const KUNYA_HERO_VIEWPORT_SELECTOR = '[data-kunya-hero-viewport]';
const KUNYA_HERO_PREV_SELECTOR = '[data-kunya-hero-prev]';
const KUNYA_HERO_NEXT_SELECTOR = '[data-kunya-hero-next]';
const KUNYA_HERO_SLIDE_SELECTOR = '[data-kunya-hero-slide]';

const setHeroNavButtonState = (button, disabled) => {
    if (!button) {
        return;
    }

    button.disabled = disabled;
    button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
};

const initKunyaHeroCarousel = () => {
    const hero = document.querySelector(KUNYA_HERO_SELECTOR);
    const viewport = hero ? hero.querySelector(KUNYA_HERO_VIEWPORT_SELECTOR) : null;
    const prevButton = hero ? hero.querySelector(KUNYA_HERO_PREV_SELECTOR) : null;
    const nextButton = hero ? hero.querySelector(KUNYA_HERO_NEXT_SELECTOR) : null;
    const slides = hero ? Array.from(hero.querySelectorAll(KUNYA_HERO_SLIDE_SELECTOR)) : [];

    if (!hero || !viewport || slides.length <= 1 || typeof EmblaCarousel !== 'function') {
        setHeroNavButtonState(prevButton, true);
        setHeroNavButtonState(nextButton, true);
        return;
    }

    const embla = EmblaCarousel(viewport, {
        axis: 'x',
        align: 'start',
        containScroll: false,
        dragFree: false,
        loop: true,
        skipSnaps: false,
        duration: kunyaReducedMotion ? 20 : 32,
    });

    const syncHeroNavButtons = () => {
        setHeroNavButtonState(prevButton, !embla.canScrollPrev());
        setHeroNavButtonState(nextButton, !embla.canScrollNext());
    };

    prevButton?.addEventListener('click', () => embla.scrollPrev());
    nextButton?.addEventListener('click', () => embla.scrollNext());

    embla.on('select', syncHeroNavButtons);
    embla.on('reInit', syncHeroNavButtons);
    syncHeroNavButtons();
};

const kunyaReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const kunyaIsPhone = window.matchMedia('(max-width: 48rem)').matches;

const KUNYA_LAYOUT_MEDIA_SELECTOR = '.kuhnya-layout-media';

const initKunyaLayoutParallax = () => {
    const medias = Array.from(document.querySelectorAll(`#details ${KUNYA_LAYOUT_MEDIA_SELECTOR}`));
    if (!medias.length) {
        return;
    }

    let ticking = false;

    const update = () => {
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        medias.forEach((media) => {
            if (kunyaReducedMotion || kunyaIsPhone) {
                media.style.setProperty('--kuhnya-layout-parallax-y', '0px');
                media.style.setProperty('--kuhnya-layout-parallax-scale', '0');
                return;
            }

            const rect = media.getBoundingClientRect();
            const progress = clamp((viewportHeight - rect.top) / (viewportHeight + rect.height), 0, 1);
            const parallaxY = (progress - 0.5) * 64;
            const parallaxScale = progress * 0.04;

            media.style.setProperty('--kuhnya-layout-parallax-y', `${parallaxY}px`);
            media.style.setProperty('--kuhnya-layout-parallax-scale', `${parallaxScale}`);
        });

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
        initKunyaHeroCarousel();
        initKunyaLayoutParallax();
    });
} else {
    initKunyaHeroCarousel();
    initKunyaLayoutParallax();
}
