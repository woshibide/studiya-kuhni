const NAV_CONTACT_TRIGGER_SELECTOR = '[data-open-nav-contact]';
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

        if (currentY <= topRevealThreshold || isContactOpen) {
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

    shells.forEach((shell) => {
        const toggle = shell.querySelector('.nav-contact-toggle');
        const panel = shell.querySelector('.nav-contact-panel');
        const close = shell.querySelector('.nav-contact-panel__close');

        if (!toggle || !panel) return;

        if (shell.dataset.contactReady === 'true') return;
        shell.dataset.contactReady = 'true';

        const setOpen = (state) => {
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
    });
};

const initGlobalNavbarContactTriggers = () => {
    if (document.documentElement.dataset.navContactTriggerReady === 'true') {
        return;
    }

    document.documentElement.dataset.navContactTriggerReady = 'true';

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest(NAV_CONTACT_TRIGGER_SELECTOR);
        if (!trigger) {
            return;
        }

        event.preventDefault();

        const toggle = document.querySelector('[data-nav-contact] .nav-contact-toggle');
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
        initGlobalNavbarContactTriggers();
    });
} else {
    initSmartNavbar();
    initNavbarContact();
    initGlobalNavbarContactTriggers();
}
