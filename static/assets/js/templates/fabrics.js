const FABRIC_GRID_SELECTOR = '.fabric-grid';
const FABRIC_CARD_SELECTOR = '[data-fabric-card]';
const FABRIC_KITCHEN_LINK_SELECTOR = 'ul a[data-fabric-image]';
const FABRIC_ITEM_SELECTOR = '[data-fabric-item]';
const FABRIC_EMBLA_SLIDE_SELECTOR = '.fabric-card__media-slide';
const FABRICS_AUTOPLAY_INTERVAL_MS = 5000;
const fabricsReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const fabricsCardState = new WeakMap();
let fabricsCleanup = null;

const normalizeIndex = (index, count) => {
    if (count <= 1) {
        return 0;
    }

    return ((index % count) + count) % count;
};

const getFabricsCardState = (card) => {
    if (fabricsCardState.has(card)) {
        return fabricsCardState.get(card);
    }

    const links = Array.from(card.querySelectorAll(FABRIC_KITCHEN_LINK_SELECTOR));
    const slides = Array.from(card.querySelectorAll(FABRIC_EMBLA_SLIDE_SELECTOR));
    const state = {
        links,
        slides,
        activeIndex: 0,
        autoplayTimerId: null,
    };

    fabricsCardState.set(card, state);
    return state;
};

const getCardFrameCount = (card) => {
    const state = getFabricsCardState(card);
    return Math.max(state.links.length, state.slides.length);
};

const setActiveKitchenLink = (card, activeIndex) => {
    const state = getFabricsCardState(card);
    if (!state.links.length) {
        return;
    }

    const normalizedIndex = normalizeIndex(activeIndex, state.links.length);

    state.links.forEach((link, index) => {
        const isActive = index === normalizedIndex;
        link.classList.toggle('is-active', isActive);

        if (isActive) {
            link.setAttribute('aria-current', 'true');
        } else {
            link.removeAttribute('aria-current');
        }
    });

};

const setActiveSlide = (card, activeIndex) => {
    const state = getFabricsCardState(card);
    if (!state.slides.length) {
        return;
    }

    const normalizedIndex = normalizeIndex(activeIndex, state.slides.length);

    state.slides.forEach((slide, index) => {
        const isActive = index === normalizedIndex;
        slide.classList.toggle('is-active', isActive);
        slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
    });
};

const setCardByIndex = (card, index) => {
    const state = getFabricsCardState(card);
    const count = getCardFrameCount(card);

    if (count === 0) {
        return;
    }

    const normalized = normalizeIndex(index, count);

    state.activeIndex = normalized;

    setActiveSlide(card, normalized);
    setActiveKitchenLink(card, normalized);
};

const clearCardAutoplay = (card) => {
    const state = getFabricsCardState(card);
    if (!state.autoplayTimerId) {
        return;
    }

    window.clearTimeout(state.autoplayTimerId);
    state.autoplayTimerId = null;
};

const getKitchenLinkIndex = (state, link) => {
    const datasetIndex = Number(link.dataset.fabricSlideIndex);
    if (Number.isInteger(datasetIndex) && datasetIndex >= 0) {
        return datasetIndex;
    }

    return state.links.indexOf(link);
};

const shouldCardAutoplay = (card) => {
    if (fabricsReducedMotion) {
        return false;
    }

    if (getCardFrameCount(card) <= 1) {
        return false;
    }

    const grid = card.closest(FABRIC_GRID_SELECTOR);
    if (!grid) {
        return true;
    }

    const expandedCard = grid.querySelector(`${FABRIC_CARD_SELECTOR}.is-expanded`);
    if (!expandedCard) {
        return true;
    }

    return expandedCard === card;
};

const queueCardAutoplay = (card, delay = FABRICS_AUTOPLAY_INTERVAL_MS) => {
    clearCardAutoplay(card);

    if (!shouldCardAutoplay(card)) {
        return;
    }

    const state = getFabricsCardState(card);
    state.autoplayTimerId = window.setTimeout(() => {
        state.autoplayTimerId = null;

        if (!shouldCardAutoplay(card)) {
            return;
        }

        setCardByIndex(card, state.activeIndex + 1);

        queueCardAutoplay(card, FABRICS_AUTOPLAY_INTERVAL_MS);
    }, delay);
};

const syncCardsAutoplay = (cards) => {
    cards.forEach((card) => {
        clearCardAutoplay(card);
    });

    cards.forEach((card) => {
        queueCardAutoplay(card);
    });
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
    });

    syncCardsAutoplay(cards);
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

    cards.forEach((card) => {
        getFabricsCardState(card);
        setCardByIndex(card, 0);
        clearCardAutoplay(card);
    });

    syncCardsAutoplay(cards);

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
        const linkIndex = getKitchenLinkIndex(state, link);
        if (linkIndex >= 0) {
            setCardByIndex(card, linkIndex);
        }

        clearCardAutoplay(card);
    };

    const onKitchenMouseOut = (event) => {
        const link = event.target.closest(FABRIC_KITCHEN_LINK_SELECTOR);
        if (!link || !grid.contains(link)) {
            return;
        }

        if (link.contains(event.relatedTarget)) {
            return;
        }

        const card = link.closest(FABRIC_CARD_SELECTOR);
        if (!card) {
            return;
        }

        const nextLink = event.relatedTarget instanceof Element
            ? event.relatedTarget.closest(FABRIC_KITCHEN_LINK_SELECTOR)
            : null;

        if (nextLink && card.contains(nextLink)) {
            return;
        }

        queueCardAutoplay(card);
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
        const linkIndex = getKitchenLinkIndex(state, link);
        if (linkIndex >= 0) {
            setCardByIndex(card, linkIndex);
        }
    };

    grid.addEventListener('click', onGridClick);
    grid.addEventListener('mouseover', onKitchenHover);
    grid.addEventListener('mouseout', onKitchenMouseOut);
    grid.addEventListener('focusin', onKitchenFocus);

    fabricsCleanup = () => {
        cards.forEach((card) => {
            clearCardAutoplay(card);
        });

        grid.removeEventListener('click', onGridClick);
        grid.removeEventListener('mouseover', onKitchenHover);
        grid.removeEventListener('mouseout', onKitchenMouseOut);
        grid.removeEventListener('focusin', onKitchenFocus);
    };

    setExpandedCard(grid, cards, null);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFabricsCards);
} else {
    initFabricsCards();
}
