const NAV_CONTACT_TRIGGER_SELECTOR = '[data-open-nav-contact]';

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
        initNavbarContact();
        initGlobalNavbarContactTriggers();
    });
} else {
    initNavbarContact();
    initGlobalNavbarContactTriggers();
}
