const setupFaq = () => {
    const faqRoot = document.querySelector('#faq.section-wrapper');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!faqRoot) {
        return;
    }

    const tabs = Array.from(faqRoot.querySelectorAll('[data-faq-tab]'));
    const panels = Array.from(faqRoot.querySelectorAll('[data-faq-panel]'));

    if (!tabs.length || !panels.length) {
        return;
    }

    const setTab = (slug, focusTab = false) => {
        tabs.forEach((tab) => {
            const isActive = tab.dataset.faqTab === slug;
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.setAttribute('tabindex', isActive ? '0' : '-1');

            if (focusTab && isActive) {
                tab.focus();
            }
        });

        panels.forEach((panel) => {
            const isActive = panel.dataset.faqPanel === slug;
            panel.hidden = !isActive;
        });
    };

    const openAnswer = (questionButton) => {
        const answerWrapId = questionButton.getAttribute('aria-controls');
        const answerWrap = answerWrapId ? faqRoot.querySelector(`#${answerWrapId}`) : null;

        if (!answerWrap) {
            return;
        }

        answerWrap.hidden = false;
        questionButton.setAttribute('aria-expanded', 'true');

        if (prefersReducedMotion) {
            answerWrap.style.height = 'auto';
            return;
        }

        answerWrap.style.height = '0px';

        requestAnimationFrame(() => {
            answerWrap.style.height = `${answerWrap.scrollHeight}px`;
        });

        const onTransitionEnd = (event) => {
            if (event.propertyName !== 'height') {
                return;
            }

            answerWrap.style.height = 'auto';
            answerWrap.removeEventListener('transitionend', onTransitionEnd);
        };

        answerWrap.addEventListener('transitionend', onTransitionEnd);
    };

    const closeAnswer = (questionButton) => {
        const answerWrapId = questionButton.getAttribute('aria-controls');
        const answerWrap = answerWrapId ? faqRoot.querySelector(`#${answerWrapId}`) : null;

        if (!answerWrap) {
            return;
        }

        if (prefersReducedMotion) {
            answerWrap.style.height = '0px';
            answerWrap.hidden = true;
            questionButton.setAttribute('aria-expanded', 'false');
            return;
        }

        answerWrap.style.height = `${answerWrap.scrollHeight}px`;

        requestAnimationFrame(() => {
            answerWrap.style.height = '0px';
        });

        questionButton.setAttribute('aria-expanded', 'false');

        const onTransitionEnd = (event) => {
            if (event.propertyName !== 'height') {
                return;
            }

            answerWrap.hidden = true;
            answerWrap.removeEventListener('transitionend', onTransitionEnd);
        };

        answerWrap.addEventListener('transitionend', onTransitionEnd);
    };

    faqRoot.addEventListener('click', (event) => {
        const tabButton = event.target.closest('[data-faq-tab]');
        if (tabButton) {
            setTab(tabButton.dataset.faqTab);
            return;
        }

        const questionButton = event.target.closest('[data-faq-question]');
        if (!questionButton) {
            return;
        }

        const isExpanded = questionButton.getAttribute('aria-expanded') === 'true';
        if (isExpanded) {
            closeAnswer(questionButton);
        } else {
            openAnswer(questionButton);
        }
    });

    faqRoot.addEventListener('keydown', (event) => {
        const currentTab = event.target.closest('[data-faq-tab]');
        if (!currentTab) {
            return;
        }

        const currentIndex = tabs.indexOf(currentTab);
        if (currentIndex < 0) {
            return;
        }

        const moveFocus = (nextIndex) => {
            const tab = tabs[nextIndex];
            if (!tab) {
                return;
            }

            setTab(tab.dataset.faqTab, true);
        };

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            moveFocus((currentIndex + 1) % tabs.length);
        }

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            moveFocus((currentIndex - 1 + tabs.length) % tabs.length);
        }

        if (event.key === 'Home') {
            event.preventDefault();
            moveFocus(0);
        }

        if (event.key === 'End') {
            event.preventDefault();
            moveFocus(tabs.length - 1);
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupFaq);
} else {
    setupFaq();
}
