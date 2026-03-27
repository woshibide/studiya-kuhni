const FABRIC_GRID_SELECTOR = '.fabric-grid';
const FABRIC_CARD_SELECTOR = '[data-fabric-card]';
const FABRIC_MEDIA_SELECTOR = '.fabric-card__media';
const FABRIC_KITCHEN_LINK_SELECTOR = 'ul a[data-fabric-image]';
const FABRIC_ITEM_SELECTOR = '[data-fabric-item]';
const FABRIC_EMBLA_VIEWPORT_SELECTOR = '[data-fabric-embla-viewport]';
const FABRIC_EMBLA_SLIDE_SELECTOR = '.fabric-card__media-slide';
const FABRICS_AUTOPLAY_INTERVAL_MS = 3000;
const FABRICS_EMBLA_DEBUG = window.location.search.includes('emblaDebug=1') || window.localStorage.getItem('emblaDebug') === '1';
const fabricsReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const fabricsCardState = new WeakMap();
let fabricsCleanup = null;

const getFabricsCardState = (card) => {
    if (fabricsCardState.has(card)) {
        return fabricsCardState.get(card);
    }

    const links = Array.from(card.querySelectorAll(FABRIC_KITCHEN_LINK_SELECTOR));
    const state = {
        links,
        activeIndex: 0,
        emblaApi: null,
        isPointerInside: false,
        isFocusInside: false,
        autoplayTimerId: null,
    };

    fabricsCardState.set(card, state);
    return state;
};

const clearFabricsAutoplay = (card) => {
    const state = getFabricsCardState(card);
    if (state.autoplayTimerId) {
        window.clearTimeout(state.autoplayTimerId);
        state.autoplayTimerId = null;
    }
};

const destroyFabricsEmbla = (card) => {
    const state = getFabricsCardState(card);
    if (state.emblaApi) {
        state.emblaApi.destroy();
        state.emblaApi = null;
    }
};

const getFabricsSlideCount = (state) => {
    if (!state.emblaApi) {
        return 0;
    }

    return state.emblaApi.scrollSnapList().length;
};

const setActiveKitchenLink = (card, activeIndex) => {
    const state = getFabricsCardState(card);
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

const resetActiveKitchenLink = (card) => {
    const state = getFabricsCardState(card);
    if (!state.links.length) {
        return;
    }

    setActiveKitchenLink(card, 0);
};

const getKitchenLinkIndex = (state, link) => {
    const datasetIndex = Number(link.dataset.fabricSlideIndex);
    if (Number.isInteger(datasetIndex) && datasetIndex >= 0) {
        return datasetIndex;
    }

    return state.links.indexOf(link);
};

const setCardByIndex = (card, index, options = {}) => {
    const { jump = false } = options;
    const state = getFabricsCardState(card);

    if (!state.links.length) {
        return;
    }

    const normalized = ((index % state.links.length) + state.links.length) % state.links.length;
    setActiveKitchenLink(card, normalized);

    if (state.emblaApi) {
        state.emblaApi.scrollTo(normalized, jump);
    }
};

const initCardEmbla = (card) => {
    const state = getFabricsCardState(card);
    const media = card.querySelector(FABRIC_MEDIA_SELECTOR);
    if (!media) {
        return;
    }

    const viewport = media.querySelector(FABRIC_EMBLA_VIEWPORT_SELECTOR);
    if (!viewport) {
        return;
    }

    destroyFabricsEmbla(card);

    const slideCount = media.querySelectorAll(FABRIC_EMBLA_SLIDE_SELECTOR).length;
    if (slideCount <= 1 || typeof EmblaCarousel !== 'function') {
        setCardByIndex(card, 0, { jump: true });
        return;
    }

    state.emblaApi = EmblaCarousel(viewport, {
        axis: 'y',
        loop: true,
        align: 'start',
        containScroll: false,
        dragFree: false,
        duration: fabricsReducedMotion ? 20 : 32,
    });

    const syncSelection = () => {
        if (!state.emblaApi) {
            return;
        }

        setActiveKitchenLink(card, state.emblaApi.selectedScrollSnap());
    };

    state.emblaApi.on('select', syncSelection);
    state.emblaApi.on('reInit', syncSelection);
    syncSelection();
};

const shouldCardAutoplay = (card) => {
    if (fabricsReducedMotion) {
        return false;
    }

    const grid = card.closest(FABRIC_GRID_SELECTOR);
    if (grid) {
        const hasExpandedCard = Boolean(grid.querySelector(`${FABRIC_CARD_SELECTOR}.is-expanded`));
        if (hasExpandedCard && !card.classList.contains('is-expanded')) {
            return false;
        }
    }

    const state = getFabricsCardState(card);

    if (!state.emblaApi || getFabricsSlideCount(state) <= 1) {
        return false;
    }

    if (!state.isPointerInside && !state.isFocusInside) {
        return false;
    }

    return true;
};

const advanceCardAutoplay = (card) => {
    if (!shouldCardAutoplay(card)) {
        return;
    }

    const state = getFabricsCardState(card);
    state.emblaApi.scrollNext();
};

const queueFabricsAutoplay = (card, delay = FABRICS_AUTOPLAY_INTERVAL_MS) => {
    clearFabricsAutoplay(card);

    if (!shouldCardAutoplay(card)) {
        return;
    }

    const state = getFabricsCardState(card);
    state.autoplayTimerId = window.setTimeout(() => {
        state.autoplayTimerId = null;

        if (!shouldCardAutoplay(card)) {
            return;
        }

        advanceCardAutoplay(card);
        queueFabricsAutoplay(card, FABRICS_AUTOPLAY_INTERVAL_MS);
    }, delay);
};

const setupCardAutoplay = (card) => {
    clearFabricsAutoplay(card);

    const state = getFabricsCardState(card);
    if (fabricsReducedMotion || !state.emblaApi || getFabricsSlideCount(state) <= 1) {
        return;
    }
};

const setExpandedCard = (grid, cards, nextExpandedCard) => {
    grid.classList.toggle('has-expanded', Boolean(nextExpandedCard));

    cards.forEach((card) => {
        const isExpanded = card === nextExpandedCard;
        card.classList.toggle('is-expanded', isExpanded);

        const cardItem = card.closest(FABRIC_ITEM_SELECTOR);
        if (cardItem) {
            cardItem.classList.toggle('is-expanded', isExpanded);
        }

        resetActiveKitchenLink(card);
        setCardByIndex(card, 0, { jump: true });
    });
};

const initFabricsCards = () => {
    if (fabricsCleanup) {
        fabricsCleanup();
        fabricsCleanup = null;
    }

    const grid = document.querySelector(FABRIC_GRID_SELECTOR);
    if (!grid) {
        return;
    }

    const cards = Array.from(grid.querySelectorAll(FABRIC_CARD_SELECTOR));
    if (!cards.length) {
        return;
    }

    let fabricsEmblaInitializedCount = 0;

    const cleanupHandlers = [];

    cards.forEach((card) => {
        getFabricsCardState(card);
        initCardEmbla(card);
        resetActiveKitchenLink(card);
        setCardByIndex(card, 0, { jump: true });
        setupCardAutoplay(card);
        fabricsEmblaInitializedCount += 1;

        cleanupHandlers.push(() => {
            clearFabricsAutoplay(card);
            destroyFabricsEmbla(card);
        });
    });

    if (FABRICS_EMBLA_DEBUG) {
        console.info('[fabrics embla debug] initialized immediately', {
            totalCards: cards.length,
            initializedCards: fabricsEmblaInitializedCount,
            hasIntersectionObserverInit: false,
        });
    }

    const onGridClick = (event) => {
        if (event.target.closest('a')) {
            return;
        }

        const card = event.target.closest(FABRIC_CARD_SELECTOR);
        if (!card || !grid.contains(card)) {
            return;
        }

        const activeCard = cards.find((item) => item.classList.contains('is-expanded'));
        const nextExpandedCard = activeCard === card ? null : card;

        setExpandedCard(grid, cards, nextExpandedCard);
    };

    const onKitchenHover = (event) => {
        const link = event.target.closest(FABRIC_KITCHEN_LINK_SELECTOR);
        if (!link || !grid.contains(link)) {
            return;
        }

        const card = link.closest(FABRIC_CARD_SELECTOR);
        if (!card || !card.classList.contains('is-expanded')) {
            return;
        }

        const state = getFabricsCardState(card);
        state.isPointerInside = true;
        queueFabricsAutoplay(card);

        const linkIndex = getKitchenLinkIndex(state, link);
        if (linkIndex >= 0) {
            setCardByIndex(card, linkIndex);
        }
    };

    const onKitchenFocus = (event) => {
        const link = event.target.closest(FABRIC_KITCHEN_LINK_SELECTOR);
        if (!link || !grid.contains(link)) {
            return;
        }

        const card = link.closest(FABRIC_CARD_SELECTOR);
        if (!card || !card.classList.contains('is-expanded')) {
            return;
        }

        const state = getFabricsCardState(card);
        state.isFocusInside = true;
        queueFabricsAutoplay(card);

        const linkIndex = getKitchenLinkIndex(state, link);
        if (linkIndex >= 0) {
            setCardByIndex(card, linkIndex);
        }
    };

    const onCardMouseOver = (event) => {
        const card = event.target.closest(FABRIC_CARD_SELECTOR);
        if (!card || !grid.contains(card)) {
            return;
        }

        if (card.contains(event.relatedTarget)) {
            return;
        }

        const state = getFabricsCardState(card);
        state.isPointerInside = true;
        queueFabricsAutoplay(card);
    };

    const onCardMouseOut = (event) => {
        const card = event.target.closest(FABRIC_CARD_SELECTOR);
        if (!card || !grid.contains(card)) {
            return;
        }

        if (card.contains(event.relatedTarget)) {
            return;
        }

        const state = getFabricsCardState(card);
        state.isPointerInside = false;
        clearFabricsAutoplay(card);
        queueFabricsAutoplay(card);
    };

    const onCardFocusIn = (event) => {
        const card = event.target.closest(FABRIC_CARD_SELECTOR);
        if (!card || !grid.contains(card)) {
            return;
        }

        const state = getFabricsCardState(card);
        state.isFocusInside = true;
        queueFabricsAutoplay(card);
    };

    const onCardFocusOut = (event) => {
        const card = event.target.closest(FABRIC_CARD_SELECTOR);
        if (!card || !grid.contains(card)) {
            return;
        }

        if (card.contains(event.relatedTarget)) {
            return;
        }

        const state = getFabricsCardState(card);
        state.isFocusInside = false;
        clearFabricsAutoplay(card);
        queueFabricsAutoplay(card);
    };

    grid.addEventListener('click', onGridClick);
    grid.addEventListener('mouseover', onKitchenHover);
    grid.addEventListener('focusin', onKitchenFocus);
    grid.addEventListener('mouseover', onCardMouseOver);
    grid.addEventListener('mouseout', onCardMouseOut);
    grid.addEventListener('focusin', onCardFocusIn);
    grid.addEventListener('focusout', onCardFocusOut);

    cleanupHandlers.push(() => {
        grid.removeEventListener('click', onGridClick);
        grid.removeEventListener('mouseover', onKitchenHover);
        grid.removeEventListener('focusin', onKitchenFocus);
        grid.removeEventListener('mouseover', onCardMouseOver);
        grid.removeEventListener('mouseout', onCardMouseOut);
        grid.removeEventListener('focusin', onCardFocusIn);
        grid.removeEventListener('focusout', onCardFocusOut);
    });

    fabricsCleanup = () => {
        cleanupHandlers.forEach((cleanup) => cleanup());
    };

    setExpandedCard(grid, cards, null);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFabricsCards);
} else {
    initFabricsCards();
}

document.addEventListener('swup:content:replace', initFabricsCards);
document.addEventListener('swup:page:view', initFabricsCards);
