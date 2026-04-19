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
            setOpen(!shell.classList.contains('is-open'));
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
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNavbarMenus);
} else {
    initNavbarMenus();
}
