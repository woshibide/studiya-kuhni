const NAV_CONTACT_TRIGGER_SELECTOR = '[data-open-nav-contact]';
const NAV_MENU_TRIGGER_SELECTOR = '[data-open-nav-menu]';
const SMART_NAV_MAX_WIDTH = 1024;

const initSmartNavbar = () => {
    const nav = document.querySelector('nav');
    if (!nav) return;

    if (nav.dataset.smartNavReady === 'true') return;
    nav.dataset.smartNavReady = 'true';

    document.documentElement.classList.add('has-smart-nav');

    let lastY = window.scrollY || 0;
    let ticking = false;
    const hideThreshold = 8;
    const topRevealThreshold = 24;

    const setNavHeightVar = () => {
        const navHeight = Math.ceil(nav.getBoundingClientRect().height);
        if (navHeight > 0) {
            document.documentElement.style.setProperty('--mobile-nav-height', `${navHeight + 8}px`);
        }
    };

    const updateNavbarState = () => {
        ticking = false;

        const isNarrowViewport = window.matchMedia(`(max-width: ${SMART_NAV_MAX_WIDTH / 16}rem)`).matches;
        if (!isNarrowViewport) {
            nav.classList.remove('is-nav-hidden');
            lastY = window.scrollY || 0;
            setNavHeightVar();
            return;
        }

        const currentY = window.scrollY || 0;
        const delta = currentY - lastY;
        const isContactOpen = Boolean(document.querySelector('[data-nav-contact].is-open'));
        const isMenuOpen = Boolean(document.querySelector('[data-nav-menu].is-open'));

        // keep home and contact controls always visible
        // only hide nav-left list on scroll down
        if (currentY <= topRevealThreshold || isContactOpen || isMenuOpen) {
            nav.classList.remove('is-nav-hidden');
        } else if (delta > hideThreshold) {
            nav.classList.add('is-nav-hidden');
        } else if (delta < -hideThreshold) {
            nav.classList.remove('is-nav-hidden');
        }

        lastY = currentY;
        setNavHeightVar();
    };

    const onScroll = () => {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(updateNavbarState);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', updateNavbarState, { passive: true });
    setNavHeightVar();
    updateNavbarState();
};

const initNavbarContact = () => {
    const shells = document.querySelectorAll('[data-nav-contact]');
    const tabletBreakpoint = window.matchMedia(`(max-width: ${SMART_NAV_MAX_WIDTH / 16}rem)`);
    const mobileBreakpoint = window.matchMedia('(max-width: 48rem)');

    const getPxNumber = (value) => {
        const parsed = Number.parseFloat(value || '0');
        return Number.isFinite(parsed) ? parsed : 0;
    };

    shells.forEach((shell) => {
        const nav = document.querySelector('nav');
        const toggle = shell.querySelector('.nav-contact-toggle');
        const panel = shell.querySelector('.nav-contact-panel');
        const close = shell.querySelector('.nav-contact-panel__close');

        if (!nav || !toggle || !panel) return;

        if (shell.dataset.contactReady === 'true') return;
        shell.dataset.contactReady = 'true';

        const updatePanelGeometry = () => {
            if (!tabletBreakpoint.matches) {
                panel.style.removeProperty('left');
                panel.style.removeProperty('top');
                panel.style.removeProperty('width');
                panel.style.removeProperty('max-width');
                panel.style.removeProperty('right');
                return;
            }

            const navRect = nav.getBoundingClientRect();
            const shellRect = shell.getBoundingClientRect();
            const toggleRect = toggle.getBoundingClientRect();

            const navStyles = window.getComputedStyle(nav);
            const gap = getPxNumber(navStyles.columnGap || navStyles.gap);

            const rootStyles = window.getComputedStyle(document.documentElement);
            const configuredColumns = Number.parseInt(rootStyles.getPropertyValue('--grid-columns'), 10);
            const totalColumns = Number.isFinite(configuredColumns) && configuredColumns > 0
                ? configuredColumns
                : (mobileBreakpoint.matches ? 4 : 8);

            const isMobileGrid = totalColumns <= 4;
            const span = isMobileGrid ? 3 : 4;
            const startColumn = isMobileGrid ? 2 : (totalColumns - span + 1);

            const colWidth = (navRect.width - (gap * (totalColumns - 1))) / totalColumns;
            const panelWidth = (colWidth * span) + (gap * (span - 1));
            const panelLeftInNav = (startColumn - 1) * (colWidth + gap);
            const shellLeftInNav = shellRect.left - navRect.left;
            const panelLeftInShell = panelLeftInNav - shellLeftInNav;
            const panelTopInShell = toggleRect.bottom - shellRect.top;

            panel.style.left = `${Math.round(panelLeftInShell)}px`;
            panel.style.top = `${Math.round(panelTopInShell)}px`;
            panel.style.right = 'auto';
            panel.style.width = `${Math.round(panelWidth)}px`;
            panel.style.maxWidth = `${Math.round(panelWidth)}px`;
        };

        const setOpen = (state) => {
            if (state) {
                updatePanelGeometry();
            }

            shell.classList.toggle('is-open', state);
            toggle.setAttribute('aria-expanded', String(state));
            panel.setAttribute('aria-hidden', String(!state));
            panel.hidden = !state;
        };

        toggle.addEventListener('click', () => {
            setOpen(!shell.classList.contains('is-open'));
        });

        close?.addEventListener('click', () => {
            setOpen(false);
            toggle.focus();
        });

        document.addEventListener('click', (event) => {
            if (!shell.classList.contains('is-open')) return;
            if (shell.contains(event.target)) return;
            setOpen(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            if (!shell.classList.contains('is-open')) return;
            setOpen(false);
            toggle.focus();
        });

        const onViewportChange = () => {
            if (!shell.classList.contains('is-open')) return;
            updatePanelGeometry();
        };

        window.addEventListener('resize', onViewportChange, { passive: true });
        updatePanelGeometry();
    });
};

// New: init menu that mirrors contact logic but anchors to left and uses .nav-menu-panel
const initNavbarMenu = () => {
    const shells = document.querySelectorAll('[data-nav-menu]');
    const tabletBreakpoint = window.matchMedia(`(max-width: ${SMART_NAV_MAX_WIDTH / 16}rem)`);
    const mobileBreakpoint = window.matchMedia('(max-width: 48rem)');

    const getPxNumber = (value) => {
        const parsed = Number.parseFloat(value || '0');
        return Number.isFinite(parsed) ? parsed : 0;
    };

    shells.forEach((shell) => {
        const nav = document.querySelector('nav');
        const toggle = shell.querySelector('.nav-contact-toggle');
        const panel = shell.querySelector('.nav-menu-panel');
        const close = shell.querySelector('.nav-contact-panel__close');

        if (!nav || !toggle || !panel) return;

        if (shell.dataset.contactReady === 'true') return;
        shell.dataset.contactReady = 'true';

        const updatePanelGeometry = () => {
            if (!tabletBreakpoint.matches) {
                panel.style.removeProperty('left');
                panel.style.removeProperty('top');
                panel.style.removeProperty('width');
                panel.style.removeProperty('max-width');
                panel.style.removeProperty('right');
                return;
            }

            const navRect = nav.getBoundingClientRect();
            const shellRect = shell.getBoundingClientRect();
            const toggleRect = toggle.getBoundingClientRect();

            const navStyles = window.getComputedStyle(nav);
            const gap = getPxNumber(navStyles.columnGap || navStyles.gap);

            const rootStyles = window.getComputedStyle(document.documentElement);
            const configuredColumns = Number.parseInt(rootStyles.getPropertyValue('--grid-columns'), 10);
            const totalColumns = Number.isFinite(configuredColumns) && configuredColumns > 0
                ? configuredColumns
                : (mobileBreakpoint.matches ? 4 : 8);

            const isMobileGrid = totalColumns <= 4;
            // menu should be 4 columns wide on desktop, 3 on small grids — same span logic
            const span = isMobileGrid ? 3 : 4;
            // anchor menu to the leftmost grid column
            const startColumn = 1;

            const colWidth = (navRect.width - (gap * (totalColumns - 1))) / totalColumns;
            const panelWidth = (colWidth * span) + (gap * (span - 1));
            const panelLeftInNav = (startColumn - 1) * (colWidth + gap);
            const shellLeftInNav = shellRect.left - navRect.left;
            const panelLeftInShell = panelLeftInNav - shellLeftInNav;
            const panelTopInShell = toggleRect.bottom - shellRect.top;

            panel.style.left = `${Math.round(panelLeftInShell)}px`;
            panel.style.top = `${Math.round(panelTopInShell)}px`;
            panel.style.right = 'auto';
            panel.style.width = `${Math.round(panelWidth)}px`;
            panel.style.maxWidth = `${Math.round(panelWidth)}px`;
        };

        const setOpen = (state) => {
            if (state) {
                updatePanelGeometry();
            }

            shell.classList.toggle('is-open', state);
            toggle.setAttribute('aria-expanded', String(state));
            panel.setAttribute('aria-hidden', String(!state));
            panel.hidden = !state;
        };

        // close menu when any link inside the panel is clicked (tap behaviour)
        panel.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) return;
            // allow default navigation, but close the menu immediately
            setOpen(false);
        });

        toggle.addEventListener('click', () => {
            setOpen(!shell.classList.contains('is-open'));
        });

        close?.addEventListener('click', () => {
            setOpen(false);
            toggle.focus();
        });

        document.addEventListener('click', (event) => {
            if (!shell.classList.contains('is-open')) return;
            if (shell.contains(event.target)) return;
            setOpen(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            if (!shell.classList.contains('is-open')) return;
            setOpen(false);
            toggle.focus();
        });

        const onViewportChange = () => {
            if (!shell.classList.contains('is-open')) return;
            updatePanelGeometry();
        };

        window.addEventListener('resize', onViewportChange, { passive: true });
        updatePanelGeometry();
    });
};

const initGlobalNavbarContactTriggers = () => {
    if (document.documentElement.dataset.navContactTriggerReady === 'true') {
        return;
    }

    document.documentElement.dataset.navContactTriggerReady = 'true';

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest(`${NAV_CONTACT_TRIGGER_SELECTOR}, ${NAV_MENU_TRIGGER_SELECTOR}`);
        if (!trigger) {
            return;
        }

        event.preventDefault();

        let toggle;
        if (trigger.matches(NAV_MENU_TRIGGER_SELECTOR)) {
            toggle = document.querySelector('[data-nav-menu] .nav-contact-toggle');
        } else {
            toggle = document.querySelector('[data-nav-contact] .nav-contact-toggle');
        }
        if (!toggle) {
            return;
        }

        if (toggle.getAttribute('aria-expanded') !== 'true') {
            toggle.click();
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initSmartNavbar();
        initNavbarContact();
        initNavbarMenu();
        initGlobalNavbarContactTriggers();
    });
} else {
    initSmartNavbar();
    initNavbarContact();
    initNavbarMenu();
    initGlobalNavbarContactTriggers();
}
