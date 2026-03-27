(() => {
const OTHER_FABRICS_SELECTOR = '.other-fabrics-section .fabric-grid';
const OTHER_FABRIC_CARD_SELECTOR = '[data-fabric-card]';
const OTHER_FABRIC_MEDIA_SELECTOR = '.fabric-card__media';
const OTHER_FABRIC_KITCHEN_LINK_SELECTOR = 'a[data-fabric-image]';
const OTHER_FABRIC_EMBLA_VIEWPORT_SELECTOR = '[data-other-embla-viewport]';
const OTHER_FABRIC_EMBLA_SLIDE_SELECTOR = '.fabric-card__media-slide';
const OTHER_FABRICS_AUTOPLAY_INTERVAL_MS = 3000;
const OTHER_FABRICS_EMBLA_DEBUG = window.location.search.includes('emblaDebug=1') || window.localStorage.getItem('emblaDebug') === '1';
const otherFabricsReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const otherFabricsCardState = new WeakMap();
let otherFabricsCleanup = null;

const getOtherFabricCardState = (card) => {
    if (otherFabricsCardState.has(card)) {
        return otherFabricsCardState.get(card);
    }

    const links = Array.from(card.querySelectorAll(OTHER_FABRIC_KITCHEN_LINK_SELECTOR));
    const state = {
        links,
        activeIndex: 0,
        isPointerInside: false,
        isFocusInside: false,
        emblaApi: null,
        autoplayTimerId: null,
    };

    otherFabricsCardState.set(card, state);
    return state;
};

const destroyOtherFabricEmbla = (card) => {
    const state = getOtherFabricCardState(card);
    if (state.emblaApi) {
        state.emblaApi.destroy();
        state.emblaApi = null;
    }
};

const clearOtherFabricAutoplay = (card) => {
    const state = getOtherFabricCardState(card);
    if (state.autoplayTimerId) {
        window.clearTimeout(state.autoplayTimerId);
        state.autoplayTimerId = null;
    }
};

const getOtherFabricSlideCount = (state) => {
    if (!state.emblaApi) {
        return 0;
    }

    return state.emblaApi.scrollSnapList().length;
};

const setOtherFabricActiveKitchenLink = (card, activeIndex) => {
    const state = getOtherFabricCardState(card);
    if (!state.links.length) {
        state.activeIndex = 0;
        return;
    }

    const normalizedIndex = state.links.length === 1
        ? 0
        : ((activeIndex % state.links.length) + state.links.length) % state.links.length;

    state.links.forEach((link, index) => {
        const isActive = index === normalizedIndex;
        link.classList.toggle('is-active', isActive);

        if (isActive) {
            link.setAttribute('aria-current', 'true');
        } else {
            link.removeAttribute('aria-current');
        }
    });

    state.activeIndex = normalizedIndex;
};

const setOtherFabricCardByIndex = (card, index, options = {}) => {
    const { jump = false } = options;
    const state = getOtherFabricCardState(card);

    if (!state.links.length) {
        return;
    }

    const normalized = ((index % state.links.length) + state.links.length) % state.links.length;
    setOtherFabricActiveKitchenLink(card, normalized);

    if (state.emblaApi) {
        state.emblaApi.scrollTo(normalized, jump);
    }
};

const resetOtherFabricCard = (card) => {
    setOtherFabricCardByIndex(card, 0, { jump: true });
};

const getOtherFabricLinkIndex = (state, link) => {
    const datasetIndex = Number(link.dataset.otherSlideIndex);
    if (Number.isInteger(datasetIndex) && datasetIndex >= 0) {
        return datasetIndex;
    }

    return state.links.indexOf(link);
};

const initOtherFabricEmbla = (card) => {
    const state = getOtherFabricCardState(card);
    const media = card.querySelector(OTHER_FABRIC_MEDIA_SELECTOR);
    if (!media) {
        return;
    }

    const viewport = media.querySelector(OTHER_FABRIC_EMBLA_VIEWPORT_SELECTOR);
    if (!viewport) {
        return;
    }

    destroyOtherFabricEmbla(card);

    const slideCount = media.querySelectorAll(OTHER_FABRIC_EMBLA_SLIDE_SELECTOR).length;
    if (slideCount <= 1 || typeof EmblaCarousel !== 'function') {
        resetOtherFabricCard(card);
        return;
    }

    state.emblaApi = EmblaCarousel(viewport, {
        axis: 'y',
        loop: true,
        align: 'start',
        containScroll: false,
        dragFree: false,
        duration: otherFabricsReducedMotion ? 20 : 32,
    });

    const syncSelection = () => {
        if (!state.emblaApi) {
            return;
        }

        setOtherFabricActiveKitchenLink(card, state.emblaApi.selectedScrollSnap());
    };

    state.emblaApi.on('select', syncSelection);
    state.emblaApi.on('reInit', syncSelection);
    syncSelection();
};

const shouldOtherFabricAutoplay = (card) => {
    if (otherFabricsReducedMotion) {
        return false;
    }

    const state = getOtherFabricCardState(card);

    if (!state.emblaApi || getOtherFabricSlideCount(state) <= 1) {
        return false;
    }

    if (!state.isPointerInside && !state.isFocusInside) {
        return false;
    }

    return true;
};

const advanceOtherFabricAutoplay = (card) => {
    if (!shouldOtherFabricAutoplay(card)) {
        return;
    }

    const state = getOtherFabricCardState(card);
    state.emblaApi.scrollNext();
};

const queueOtherFabricAutoplay = (card, delay = OTHER_FABRICS_AUTOPLAY_INTERVAL_MS) => {
    clearOtherFabricAutoplay(card);

    if (!shouldOtherFabricAutoplay(card)) {
        return;
    }

    const state = getOtherFabricCardState(card);
    state.autoplayTimerId = window.setTimeout(() => {
        state.autoplayTimerId = null;

        if (!shouldOtherFabricAutoplay(card)) {
            return;
        }

        advanceOtherFabricAutoplay(card);
        queueOtherFabricAutoplay(card, OTHER_FABRICS_AUTOPLAY_INTERVAL_MS);
    }, delay);
};

const setupOtherFabricAutoplay = (card) => {
    clearOtherFabricAutoplay(card);

    const state = getOtherFabricCardState(card);
    if (otherFabricsReducedMotion || !state.emblaApi || getOtherFabricSlideCount(state) <= 1) {
        return;
    }
};

const initOtherFabrics = () => {
    if (otherFabricsCleanup) {
        otherFabricsCleanup();
        otherFabricsCleanup = null;
    }

    const grids = Array.from(document.querySelectorAll(OTHER_FABRICS_SELECTOR));
    if (!grids.length) {
        return;
    }

    const cleanupHandlers = [];

    grids.forEach((grid) => {
        if (grid.dataset.otherFabricsBound === 'true') {
            return;
        }

        const cards = Array.from(grid.querySelectorAll(OTHER_FABRIC_CARD_SELECTOR));
        if (!cards.length) {
            return;
        }

        let otherEmblaInitializedCount = 0;

        cards.forEach((card) => {
            getOtherFabricCardState(card);
            initOtherFabricEmbla(card);
            resetOtherFabricCard(card);
            setupOtherFabricAutoplay(card);
            otherEmblaInitializedCount += 1;

            cleanupHandlers.push(() => {
                clearOtherFabricAutoplay(card);
                destroyOtherFabricEmbla(card);
            });
        });

        if (OTHER_FABRICS_EMBLA_DEBUG) {
            console.info('[other fabrics embla debug] initialized immediately', {
                totalCards: cards.length,
                initializedCards: otherEmblaInitializedCount,
                hasIntersectionObserverInit: false,
            });
        }

        const onKitchenHover = (event) => {
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
            queueOtherFabricAutoplay(card);

            const linkIndex = getOtherFabricLinkIndex(state, link);
            if (linkIndex >= 0) {
                setOtherFabricCardByIndex(card, linkIndex);
            }
        };

        const onCardMouseOver = (event) => {
            const card = event.target.closest(OTHER_FABRIC_CARD_SELECTOR);
            if (!card || !grid.contains(card)) {
                return;
            }

            if (card.contains(event.relatedTarget)) {
                return;
            }

            const state = getOtherFabricCardState(card);
            state.isPointerInside = true;
            queueOtherFabricAutoplay(card);
        };

        const onCardMouseOut = (event) => {
            const card = event.target.closest(OTHER_FABRIC_CARD_SELECTOR);
            if (!card || !grid.contains(card)) {
                return;
            }

            if (card.contains(event.relatedTarget)) {
                return;
            }

            const state = getOtherFabricCardState(card);
            state.isPointerInside = false;
            clearOtherFabricAutoplay(card);
            queueOtherFabricAutoplay(card);
        };

        const onCardFocusIn = (event) => {
            const card = event.target.closest(OTHER_FABRIC_CARD_SELECTOR);
            if (!card || !grid.contains(card)) {
                return;
            }

            const state = getOtherFabricCardState(card);
            state.isFocusInside = true;
            queueOtherFabricAutoplay(card);

            const link = event.target.closest(OTHER_FABRIC_KITCHEN_LINK_SELECTOR);
            if (!link) {
                return;
            }

            const linkIndex = getOtherFabricLinkIndex(state, link);
            if (linkIndex >= 0) {
                setOtherFabricCardByIndex(card, linkIndex);
            }
        };

        const onCardFocusOut = (event) => {
            const card = event.target.closest(OTHER_FABRIC_CARD_SELECTOR);
            if (!card || !grid.contains(card)) {
                return;
            }

            if (card.contains(event.relatedTarget)) {
                return;
            }

            const state = getOtherFabricCardState(card);
            state.isFocusInside = false;
            clearOtherFabricAutoplay(card);
            queueOtherFabricAutoplay(card);
        };

        grid.addEventListener('mouseover', onKitchenHover);
        grid.addEventListener('focusin', onKitchenHover);
        grid.addEventListener('mouseover', onCardMouseOver);
        grid.addEventListener('mouseout', onCardMouseOut);
        grid.addEventListener('focusin', onCardFocusIn);
        grid.addEventListener('focusout', onCardFocusOut);

        cleanupHandlers.push(() => {
            grid.removeEventListener('mouseover', onKitchenHover);
            grid.removeEventListener('focusin', onKitchenHover);
            grid.removeEventListener('mouseover', onCardMouseOver);
            grid.removeEventListener('mouseout', onCardMouseOut);
            grid.removeEventListener('focusin', onCardFocusIn);
            grid.removeEventListener('focusout', onCardFocusOut);
        });

        grid.dataset.otherFabricsBound = 'true';
    });

    otherFabricsCleanup = () => {
        cleanupHandlers.forEach((cleanup) => cleanup());
    };
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOtherFabrics);
} else {
    initOtherFabrics();
}

document.addEventListener('swup:content:replace', initOtherFabrics);
document.addEventListener('swup:page:view', initOtherFabrics);
})();
