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

document.addEventListener('DOMContentLoaded', initNavbarContact);
