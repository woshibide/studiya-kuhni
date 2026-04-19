const NAV_CONTACT_TRIGGER_SELECTOR = '[data-open-nav-contact]';

const bindMenuShell = ({ shellSelector, toggleSelector, panelSelector, readyFlag }) => {
    const shells = document.querySelectorAll(shellSelector);

    shells.forEach((shell) => {
        if (shell.dataset[readyFlag] === 'true') return;

        const toggle = shell.querySelector(toggleSelector);
        const panel = shell.querySelector(panelSelector);
        if (!toggle || !panel) return;

        shell.dataset[readyFlag] = 'true';

        const setOpen = (isOpen) => {
            shell.classList.toggle('is-open', isOpen);
            toggle.setAttribute('aria-expanded', String(isOpen));
            panel.setAttribute('aria-hidden', String(!isOpen));
        };

        panel.hidden = false;
        setOpen(shell.classList.contains('is-open'));

        toggle.addEventListener('click', () => {
            const willOpen = !shell.classList.contains('is-open');
            
            // on mobile, close all others if opening
            if (willOpen && window.matchMedia('(max-width: 48rem)').matches) {
                const otherShells = document.querySelectorAll('[data-nav-menu].is-open, [data-nav-contact].is-open');
                otherShells.forEach((otherShell) => {
                    if (otherShell !== shell) {
                        const otherToggle = otherShell.querySelector('[aria-controls]');
                        if (otherToggle) otherToggle.click();
                    }
                });
            }

            setOpen(willOpen);
        });
    });
};

const initGlobalContactTriggers = () => {
    if (document.documentElement.dataset.navContactTriggerReady === 'true') {
        return;
    }

    document.documentElement.dataset.navContactTriggerReady = 'true';

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest(NAV_CONTACT_TRIGGER_SELECTOR);
        if (!trigger) return;

        event.preventDefault();

        const shell = document.querySelector('[data-nav-contact]');
        const toggle = shell?.querySelector('.nav-contact-toggle');
        if (!shell || !toggle) return;

        if (!shell.classList.contains('is-open')) {
            toggle.click();
        }
    });
};

const initNavbarMenus = () => {
    bindMenuShell({
        shellSelector: '[data-nav-menu]',
        toggleSelector: '.nav-menu-toggle',
        panelSelector: '.nav-menu-panel',
        readyFlag: 'menuReady',
    });

    bindMenuShell({
        shellSelector: '[data-nav-contact]',
        toggleSelector: '.nav-contact-toggle',
        panelSelector: '.nav-contact-panel',
        readyFlag: 'contactReady',
    });

    initGlobalContactTriggers();
    initNavbarScroll();
};

const initNavbarScroll = () => {
    if (document.documentElement.dataset.navScrollReady === 'true') return;
    document.documentElement.dataset.navScrollReady = 'true';

    let lastScrollY = window.scrollY;
    const nav = document.querySelector('nav');
    
    if (!nav) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY < 0) return; // Safari bounce
        
        // Don't hide if menu is open
        const isMenuOpen = document.querySelector('[data-nav-menu].is-open') || 
                           document.querySelector('[data-nav-contact].is-open');
        
        if (window.scrollY > lastScrollY && window.scrollY > 100 && !isMenuOpen) {
            // scrolling down
            nav.classList.add('is-nav-hidden');
        } else if (window.scrollY < lastScrollY) {
            // scrolling up
            nav.classList.remove('is-nav-hidden');
        }
        lastScrollY = window.scrollY;
    }, { passive: true });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNavbarMenus);
} else {
    initNavbarMenus();
}
