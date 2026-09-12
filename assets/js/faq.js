const setupFaq = () => {
    const faqRoot = document.querySelector('#faq.section-wrapper');

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

    const setAnswer = (questionButton, expanded) => {
        const answerId = questionButton.getAttribute('aria-controls');
        const answer = answerId ? document.getElementById(answerId) : null;
        if (!answer) return;
        questionButton.setAttribute('aria-expanded', String(expanded));
        answer.hidden = !expanded;
        answer.style.height = expanded ? 'auto' : '0px';
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
            setAnswer(questionButton, false);
        } else {
            setAnswer(questionButton, true);
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

        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
            event.preventDefault();
            moveFocus((currentIndex + 1) % tabs.length);
        }

        if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
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
