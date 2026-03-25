(() => {
const OTHER_FABRICS_SELECTOR = '.other-fabrics-section .fabric-grid';
const OTHER_FABRIC_CARD_SELECTOR = '[data-fabric-card]';
const OTHER_FABRIC_MEDIA_SELECTOR = '.fabric-card__media';
const OTHER_FABRIC_KITCHEN_LINK_SELECTOR = 'a[data-fabric-image]';
const OTHER_FABRICS_AUTOPLAY_INTERVAL_MS = 4600;
const OTHER_FABRICS_AUTOPLAY_STAGGER_MS = 520;
const OTHER_FABRICS_IMAGE_FADE_OUT_MS = 180;
const otherFabricsReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const otherFabricsCardState = new WeakMap();

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

const getOtherFabricCardState = (card) => {
    if (otherFabricsCardState.has(card)) {
        return otherFabricsCardState.get(card);
    }

    const links = Array.from(card.querySelectorAll(OTHER_FABRIC_KITCHEN_LINK_SELECTOR));
    const state = {
        links,
        activeIndex: links.length ? 0 : -1,
        isPointerInside: false,
        isFocusInside: false,
        autoplayStartTimeoutId: null,
        autoplayIntervalId: null,
        imageSwapTimeoutId: null,
    };

    otherFabricsCardState.set(card, state);
    return state;
};

const setOtherFabricImage = (card, imageUrl, options = {}) => {
    const { animate = true } = options;
    const media = card.querySelector(OTHER_FABRIC_MEDIA_SELECTOR);
    if (!media || !imageUrl) {
        return;
    }

    const state = getOtherFabricCardState(card);

    if (media.dataset.currentImage === imageUrl) {
        return;
    }

    if (!animate || otherFabricsReducedMotion) {
        media.style.backgroundImage = `url("${imageUrl.replace(/"/g, '\\"')}")`;
        media.dataset.currentImage = imageUrl;
        media.classList.remove('is-image-fading');

        if (state.imageSwapTimeoutId) {
            window.clearTimeout(state.imageSwapTimeoutId);
            state.imageSwapTimeoutId = null;
        }

        return;
    }

    if (state.imageSwapTimeoutId) {
        window.clearTimeout(state.imageSwapTimeoutId);
    }

    media.classList.add('is-image-fading');
    state.imageSwapTimeoutId = window.setTimeout(() => {
        media.style.backgroundImage = `url("${imageUrl.replace(/"/g, '\\"')}")`;
        media.dataset.currentImage = imageUrl;
        window.requestAnimationFrame(() => {
            media.classList.remove('is-image-fading');
        });
        state.imageSwapTimeoutId = null;
    }, OTHER_FABRICS_IMAGE_FADE_OUT_MS);
};

const setOtherFabricActiveKitchenLink = (card, activeLink) => {
    const state = getOtherFabricCardState(card);

    state.links.forEach((link) => {
        const isActive = link === activeLink;
        link.classList.toggle('is-active', isActive);

        if (isActive) {
            link.setAttribute('aria-current', 'true');
        } else {
            link.removeAttribute('aria-current');
        }
    });

    const nextIndex = state.links.indexOf(activeLink);
    if (nextIndex >= 0) {
        state.activeIndex = nextIndex;
    }
};

const setOtherFabricCardByIndex = (card, index, options = {}) => {
    const { animate = true } = options;
    const state = getOtherFabricCardState(card);

    if (!state.links.length) {
        return;
    }

    const normalized = ((index % state.links.length) + state.links.length) % state.links.length;
    const targetLink = state.links[normalized];

    setOtherFabricActiveKitchenLink(card, targetLink);
    setOtherFabricImage(card, targetLink.dataset.fabricImage || card.dataset.defaultImage || '/assets/placeholder.svg', { animate });
};

const resetOtherFabricCard = (card) => {
    setOtherFabricCardByIndex(card, 0, { animate: false });
};

const clearOtherFabricAutoplay = (card) => {
    const state = getOtherFabricCardState(card);

    if (state.autoplayStartTimeoutId) {
        window.clearTimeout(state.autoplayStartTimeoutId);
        state.autoplayStartTimeoutId = null;
    }

    if (state.autoplayIntervalId) {
        window.clearInterval(state.autoplayIntervalId);
        state.autoplayIntervalId = null;
    }
};

const shouldOtherFabricAutoplay = (card) => {
    const state = getOtherFabricCardState(card);

    if (state.links.length <= 1) {
        return false;
    }

    if (state.isPointerInside || state.isFocusInside) {
        return false;
    }

    return true;
};

const advanceOtherFabricAutoplay = (card) => {
    if (!shouldOtherFabricAutoplay(card)) {
        return;
    }

    const state = getOtherFabricCardState(card);
    setOtherFabricCardByIndex(card, state.activeIndex + 1);
};

const setupOtherFabricAutoplay = (card, order) => {
    clearOtherFabricAutoplay(card);

    const state = getOtherFabricCardState(card);
    if (state.links.length <= 1) {
        return;
    }

    state.autoplayStartTimeoutId = window.setTimeout(() => {
        advanceOtherFabricAutoplay(card);

        state.autoplayIntervalId = window.setInterval(() => {
            advanceOtherFabricAutoplay(card);
        }, OTHER_FABRICS_AUTOPLAY_INTERVAL_MS);
    }, order * OTHER_FABRICS_AUTOPLAY_STAGGER_MS);
};

const initOtherFabricsParallax = (grid) => {
    if (otherFabricsReducedMotion || !grid) {
        return;
    }

    const mediaItems = Array.from(grid.querySelectorAll(OTHER_FABRIC_MEDIA_SELECTOR));
    if (!mediaItems.length) {
        return;
    }

    let ticking = false;

    const update = () => {
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        mediaItems.forEach((media) => {
            const mediaRect = media.getBoundingClientRect();
            const progress = clamp((viewportHeight - mediaRect.top) / (viewportHeight + mediaRect.height), 0, 1);
            const parallaxY = (progress - 0.5) * 80;
            const parallaxScale = progress * 0.05;

            media.style.setProperty('--other-fabric-parallax-y', `${parallaxY.toFixed(2)}px`);
            media.style.setProperty('--other-fabric-parallax-scale', parallaxScale.toFixed(4));
        });

        ticking = false;
    };

    const onScroll = () => {
        if (ticking) {
            return;
        }

        ticking = true;
        window.requestAnimationFrame(update);
    };

    update();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
};

const initOtherFabrics = () => {
    const grids = Array.from(document.querySelectorAll(OTHER_FABRICS_SELECTOR));
    if (!grids.length) {
        return;
    }

    grids.forEach((grid) => {
        if (grid.dataset.otherFabricsBound === 'true') {
            return;
        }

        const cards = Array.from(grid.querySelectorAll(OTHER_FABRIC_CARD_SELECTOR));
        if (!cards.length) {
            return;
        }

        initOtherFabricsParallax(grid);

        cards.forEach((card, index) => {
            getOtherFabricCardState(card);
            resetOtherFabricCard(card);
            setupOtherFabricAutoplay(card, index);
        });

        grid.addEventListener('mouseover', (event) => {
            const link = event.target.closest(OTHER_FABRIC_KITCHEN_LINK_SELECTOR);
            if (!link || !grid.contains(link)) {
                return;
            }

            const card = link.closest(OTHER_FABRIC_CARD_SELECTOR);
            if (!card) {
                return;
            }

            const state = getOtherFabricCardState(card);
            state.isPointerInside = true;

            const linkIndex = state.links.indexOf(link);
            if (linkIndex >= 0) {
                setOtherFabricCardByIndex(card, linkIndex);
            }
        });

        grid.addEventListener('mouseover', (event) => {
            const card = event.target.closest(OTHER_FABRIC_CARD_SELECTOR);
            if (!card || !grid.contains(card)) {
                return;
            }

            if (card.contains(event.relatedTarget)) {
                return;
            }

            const state = getOtherFabricCardState(card);
            state.isPointerInside = true;
        });

        grid.addEventListener('mouseout', (event) => {
            const card = event.target.closest(OTHER_FABRIC_CARD_SELECTOR);
            if (!card || !grid.contains(card)) {
                return;
            }

            if (card.contains(event.relatedTarget)) {
                return;
            }

            const state = getOtherFabricCardState(card);
            state.isPointerInside = false;
            setOtherFabricCardByIndex(card, 0);
        });

        grid.addEventListener('focusin', (event) => {
            const card = event.target.closest(OTHER_FABRIC_CARD_SELECTOR);
            if (!card || !grid.contains(card)) {
                return;
            }

            const state = getOtherFabricCardState(card);
            state.isFocusInside = true;

            const link = event.target.closest(OTHER_FABRIC_KITCHEN_LINK_SELECTOR);
            if (!link) {
                return;
            }

            const linkIndex = state.links.indexOf(link);
            if (linkIndex >= 0) {
                setOtherFabricCardByIndex(card, linkIndex);
            }
        });

        grid.addEventListener('focusin', (event) => {
            const card = event.target.closest(OTHER_FABRIC_CARD_SELECTOR);
            if (!card || !grid.contains(card)) {
                return;
            }

            const state = getOtherFabricCardState(card);
            state.isFocusInside = true;
        });

        grid.addEventListener('focusout', (event) => {
            const card = event.target.closest(OTHER_FABRIC_CARD_SELECTOR);
            if (!card || !grid.contains(card)) {
                return;
            }

            if (card.contains(event.relatedTarget)) {
                return;
            }

            const state = getOtherFabricCardState(card);
            state.isFocusInside = false;
            setOtherFabricCardByIndex(card, 0);
        });

        grid.dataset.otherFabricsBound = 'true';
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOtherFabrics);
} else {
    initOtherFabrics();
}

document.addEventListener('swup:content:replace', initOtherFabrics);
document.addEventListener('swup:page:view', initOtherFabrics);
})();
