const FABRIC_GRID_SELECTOR = '.fabric-grid';
const FABRIC_CARD_SELECTOR = '[data-fabric-card]';
const FABRIC_MEDIA_SELECTOR = '.fabric-card__media';
const FABRIC_KITCHEN_LINK_SELECTOR = 'ul a[data-fabric-image]';

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

        setCardImage(card, card.dataset.defaultImage || '/assets/placeholder.svg');
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
