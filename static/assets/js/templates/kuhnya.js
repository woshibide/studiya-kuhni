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

const KUNYA_LAYOUT_CARD_SELECTOR = '[data-kuhnya-layout-card]';
const KUNYA_LAYOUT_TOGGLE_SELECTOR = '[data-kuhnya-layout-toggle]';
const KUNYA_LAYOUT_GRID_SELECTOR = '.kuhnya-layout-grid';
const KUNYA_LAYOUT_MEDIA_SELECTOR = '.kuhnya-layout-media';
const KUNYA_LAYOUT_EXPANDED_COL_START = '1';
const KUNYA_LAYOUT_EXPANDED_COL_SPAN = 'var(--grid-columns)';
const kunyaReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const kunyaIsPhone = window.matchMedia('(max-width: 48rem)').matches;

const setStyleVariable = (element, name, value) => {
    if (value && value !== '') {
        element.style.setProperty(name, value);
        return;
    }

    element.style.removeProperty(name);
};

const ensureCardOriginalLayout = (card) => {
    if (card.dataset.originalColStart === undefined) {
        card.dataset.originalColStart = card.style.getPropertyValue('--col-start').trim();
        card.dataset.originalColSpan = card.style.getPropertyValue('--col-span').trim();
        card.dataset.originalColStartMobile = card.style.getPropertyValue('--col-start-mobile').trim();
        card.dataset.originalColSpanMobile = card.style.getPropertyValue('--col-span-mobile').trim();
        card.dataset.originalMarginTop = card.style.getPropertyValue('margin-top').trim();
    }
};

const setCardToggleState = (card, expanded) => {
    ensureCardOriginalLayout(card);
    const toggle = card.querySelector(KUNYA_LAYOUT_TOGGLE_SELECTOR);
    if (!toggle) {
        return;
    }

    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    toggle.setAttribute('aria-label', expanded ? 'Minimize image' : 'Expand image');
};

const applyCardLayout = (card, expanded) => {
    ensureCardOriginalLayout(card);

    if (expanded) {
        setStyleVariable(card, '--col-start', KUNYA_LAYOUT_EXPANDED_COL_START);
        setStyleVariable(card, '--col-span', KUNYA_LAYOUT_EXPANDED_COL_SPAN);
        setStyleVariable(card, '--col-start-mobile', KUNYA_LAYOUT_EXPANDED_COL_START);
        setStyleVariable(card, '--col-span-mobile', KUNYA_LAYOUT_EXPANDED_COL_SPAN);
        setStyleVariable(card, 'margin-top', '0');
        return;
    }

    setStyleVariable(card, '--col-start', card.dataset.originalColStart ?? '');
    setStyleVariable(card, '--col-span', card.dataset.originalColSpan ?? '');
    setStyleVariable(card, '--col-start-mobile', card.dataset.originalColStartMobile ?? '');
    setStyleVariable(card, '--col-span-mobile', card.dataset.originalColSpanMobile ?? '');
    setStyleVariable(card, 'margin-top', card.dataset.originalMarginTop ?? '');
};

const animateKunyaLayout = (grid, mutateLayout) => {
    const cards = Array.from(grid.querySelectorAll(KUNYA_LAYOUT_CARD_SELECTOR));
    if (!cards.length) {
        mutateLayout();
        return;
    }

    const firstRects = new Map();
    cards.forEach((card) => {
        firstRects.set(card, card.getBoundingClientRect());
    });

    mutateLayout();

    if (kunyaReducedMotion) {
        return;
    }

    window.requestAnimationFrame(() => {
        cards.forEach((card) => {
            card.getAnimations().forEach((animation) => animation.cancel());

            const firstRect = firstRects.get(card);
            const lastRect = card.getBoundingClientRect();
            if (!firstRect || !lastRect) {
                return;
            }

            const deltaX = firstRect.left - lastRect.left;
            const deltaY = firstRect.top - lastRect.top;
            const scaleX = lastRect.width > 0 ? firstRect.width / lastRect.width : 1;
            const scaleY = lastRect.height > 0 ? firstRect.height / lastRect.height : 1;

            if (
                Math.abs(deltaX) < 0.5
                && Math.abs(deltaY) < 0.5
                && Math.abs(scaleX - 1) < 0.01
                && Math.abs(scaleY - 1) < 0.01
            ) {
                return;
            }

            card.animate(
                [
                    {
                        transformOrigin: 'top left',
                        transform: `translate(${deltaX}px, ${deltaY}px) scale(${scaleX}, ${scaleY})`,
                    },
                    {
                        transformOrigin: 'top left',
                        transform: 'translate(0, 0) scale(1, 1)',
                    },
                ],
                {
                    duration: 800,
                    easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                    fill: 'both',
                },
            );
        });
    });
};

const setExpandedCard = (grid, targetCard) => {
    const cards = Array.from(grid.querySelectorAll(KUNYA_LAYOUT_CARD_SELECTOR));
    const activeExpandedCard = cards.find((card) => card.classList.contains('is-expanded')) || null;

    const nextExpandedCard = targetCard && targetCard.classList.contains('is-expanded')
        ? null
        : targetCard;

    animateKunyaLayout(grid, () => {
        cards.forEach((card) => {
            const isExpanded = card === nextExpandedCard;
            card.classList.toggle('is-expanded', isExpanded);
            card.classList.toggle('is-collapsed', Boolean(nextExpandedCard) && !isExpanded);
            applyCardLayout(card, isExpanded);
            setCardToggleState(card, isExpanded);
        });

        if (activeExpandedCard && activeExpandedCard !== nextExpandedCard) {
            setCardToggleState(activeExpandedCard, false);
        }
    });
};

const initKunyaLayoutCards = () => {
    const grid = document.querySelector(KUNYA_LAYOUT_GRID_SELECTOR);
    if (!grid) {
        return;
    }

    const cards = Array.from(grid.querySelectorAll(KUNYA_LAYOUT_CARD_SELECTOR));
    if (!cards.length) {
        return;
    }

    cards.forEach((card) => {
        ensureCardOriginalLayout(card);
        const toggle = card.querySelector(KUNYA_LAYOUT_TOGGLE_SELECTOR);
        setCardToggleState(card, false);
        applyCardLayout(card, false);

        if (!toggle) {
            return;
        }

        const onToggle = (event) => {
            if (event.target.closest(KUNYA_LAYOUT_TOGGLE_SELECTOR) && event.currentTarget === card) {
                return;
            }

            setExpandedCard(grid, card);
        };

        card.addEventListener('click', onToggle);
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            setExpandedCard(grid, card);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const expandedCard = cards.find((card) => card.classList.contains('is-expanded')) || null;
        if (!expandedCard) {
            return;
        }

        setExpandedCard(grid, expandedCard);
    });
};

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
        initKunyaLayoutCards();
        initKunyaLayoutParallax();
    });
} else {
    initKunyaHeroCarousel();
    initKunyaLayoutCards();
    initKunyaLayoutParallax();
}
