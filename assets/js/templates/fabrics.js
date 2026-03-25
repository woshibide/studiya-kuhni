const FABRIC_GRID_SELECTOR = '.fabric-grid';
const FABRIC_CARD_SELECTOR = '[data-fabric-card]';
const FABRIC_MEDIA_SELECTOR = '.fabric-card__media';
const FABRIC_KITCHEN_LINK_SELECTOR = 'ul a[data-fabric-image]';
const FABRIC_ITEM_SELECTOR = '[data-fabric-item]';

const setActiveKitchenLink = (card, activeLink) => {
    const links = Array.from(card.querySelectorAll(FABRIC_KITCHEN_LINK_SELECTOR));

    links.forEach((link) => {
        const isActive = link === activeLink;
        link.classList.toggle('is-active', isActive);

        if (isActive) {
            link.setAttribute('aria-current', 'true');
        } else {
            link.removeAttribute('aria-current');
        }
    });
};

const resetActiveKitchenLink = (card) => {
    const defaultLink = card.querySelector(FABRIC_KITCHEN_LINK_SELECTOR);
    setActiveKitchenLink(card, defaultLink);
};

const setCardImage = (card, imageUrl) => {
    const media = card.querySelector(FABRIC_MEDIA_SELECTOR);
    if (!media || !imageUrl) {
        return;
    }

    media.style.backgroundImage = `url("${imageUrl.replace(/"/g, '\\"')}")`;
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

        setCardImage(card, card.dataset.defaultImage || '/assets/placeholder.svg');
        resetActiveKitchenLink(card);
    });
};

const initFabricsCards = () => {
    const grid = document.querySelector(FABRIC_GRID_SELECTOR);
    if (!grid) {
        return;
    }

    const cards = Array.from(grid.querySelectorAll(FABRIC_CARD_SELECTOR));
    if (!cards.length) {
        return;
    }

    if (!grid.dataset.fabricsBound) {
        grid.addEventListener('click', (event) => {
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
        });

        grid.addEventListener('mouseover', (event) => {
            const link = event.target.closest(FABRIC_KITCHEN_LINK_SELECTOR);
            if (!link || !grid.contains(link)) {
                return;
            }

            const card = link.closest(FABRIC_CARD_SELECTOR);
            if (!card || !card.classList.contains('is-expanded')) {
                return;
            }

            setCardImage(card, link.dataset.fabricImage);
            setActiveKitchenLink(card, link);
        });

        grid.addEventListener('mouseout', (event) => {
            const link = event.target.closest(FABRIC_KITCHEN_LINK_SELECTOR);
            if (!link || !grid.contains(link)) {
                return;
            }

            const card = link.closest(FABRIC_CARD_SELECTOR);
            if (!card || !card.classList.contains('is-expanded')) {
                return;
            }

            setCardImage(card, card.dataset.defaultImage || '/assets/placeholder.svg');
            resetActiveKitchenLink(card);
        });

        grid.addEventListener('focusin', (event) => {
            const link = event.target.closest(FABRIC_KITCHEN_LINK_SELECTOR);
            if (!link || !grid.contains(link)) {
                return;
            }

            const card = link.closest(FABRIC_CARD_SELECTOR);
            if (!card || !card.classList.contains('is-expanded')) {
                return;
            }

            setCardImage(card, link.dataset.fabricImage);
            setActiveKitchenLink(card, link);
        });

        grid.addEventListener('focusout', (event) => {
            const link = event.target.closest(FABRIC_KITCHEN_LINK_SELECTOR);
            if (!link || !grid.contains(link)) {
                return;
            }

            const card = link.closest(FABRIC_CARD_SELECTOR);
            if (!card || !card.classList.contains('is-expanded')) {
                return;
            }

            setCardImage(card, card.dataset.defaultImage || '/assets/placeholder.svg');
            resetActiveKitchenLink(card);
        });

        grid.dataset.fabricsBound = 'true';
    }

    setExpandedCard(grid, cards, null);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFabricsCards);
} else {
    initFabricsCards();
}

document.addEventListener('swup:content:replace', initFabricsCards);
document.addEventListener('swup:page:view', initFabricsCards);
