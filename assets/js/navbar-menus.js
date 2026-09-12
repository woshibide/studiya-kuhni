(() => {
    const initNavbarMenus = () => {
        const nav = document.querySelector('[data-site-nav]');
        if (!nav || nav.dataset.ready === 'true') return;
        nav.dataset.ready = 'true';
        const mobile = window.matchMedia('(max-width: 48rem)');
        const entries = Array.from(nav.querySelectorAll('[data-nav-menu], [data-nav-contact]')).map((shell) => ({
            shell,
            toggle: shell.querySelector('button[aria-controls]'),
            panel: shell.querySelector('.nav-menu-panel, .nav-contact-panel'),
            opener: null,
        })).filter(({ toggle, panel }) => toggle && panel);
        const background = new Map();
        const focusable = (root) => Array.from(root.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]')).filter((el) => !el.closest('[hidden], [inert]') && el.getClientRects().length);
        const openEntry = () => entries.find(({ shell }) => shell.classList.contains('is-open'));

        nav.querySelectorAll('[data-copy-address]').forEach((button) => {
            const status = button.closest('.nav-contact-location').querySelector('[data-address-copy-status]');
            let copying = false;
            let blink;
            let noticeTimeout;
            button.addEventListener('click', async () => {
                if (copying) return;
                copying = true;
                clearTimeout(noticeTimeout);
                status.textContent = '';
                status.classList.add('sr-only');
                try {
                    await navigator.clipboard.writeText(button.dataset.copyAddress);
                    status.classList.remove('sr-only');
                    status.textContent = 'Скопированно';
                    noticeTimeout = setTimeout(() => {
                        status.classList.add('sr-only');
                        status.textContent = '';
                    }, 2200);
                    blink?.cancel();
                    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        blink = button.animate([{ opacity: 1 }, { opacity: 0.25 }, { opacity: 1 }], {
                            duration: 360,
                            easing: 'ease-in-out',
                        });
                    }
                } catch {
                    status.classList.remove('sr-only');
                    status.textContent = 'Не удалось скопировать адрес. Выделите текст и скопируйте вручную.';
                } finally {
                    copying = false;
                }
            });
        });

        const syncMobile = () => {
            const modal = mobile.matches && Boolean(openEntry());
            document.documentElement.classList.toggle('is-nav-panel-open', modal);
            if (modal) {
                for (const node of document.body.children) {
                    if (!(node instanceof HTMLElement) || node === nav || node.contains(nav) || ['SCRIPT', 'STYLE'].includes(node.tagName)) continue;
                    if (!background.has(node)) background.set(node, node.inert);
                    node.inert = true;
                }
            } else {
                background.forEach((inert, node) => { node.inert = inert; });
                background.clear();
            }
        };

        const close = (entry, restoreFocus = false) => {
            if (!entry) return;
            if (entry.panel.contains(document.activeElement)) entry.toggle.focus({ preventScroll: true });
            entry.shell.classList.remove('is-open');
            entry.toggle.setAttribute('aria-expanded', 'false');
            entry.panel.inert = true;
            entry.panel.hidden = true;
            entry.panel.setAttribute('aria-hidden', 'true');
            syncMobile();
            if (restoreFocus) {
                const usableOpener = entry.opener?.isConnected && !entry.opener.closest('[hidden], [inert], dialog:not([open])');
                (usableOpener ? entry.opener : entry.toggle).focus({ preventScroll: true });
            }
        };

        const open = (entry, opener = entry.toggle, focusPanel = false) => {
            entries.forEach((other) => { if (other !== entry) close(other); });
            entry.opener = opener;
            entry.panel.hidden = false;
            entry.panel.inert = false;
            entry.panel.setAttribute('aria-hidden', 'false');
            entry.shell.classList.add('is-open');
            entry.toggle.setAttribute('aria-expanded', 'true');
            nav.classList.remove('is-nav-hidden');
            syncMobile();
            if (focusPanel) entry.panel.focus({ preventScroll: true });
        };

        entries.forEach((entry) => {
            entry.panel.tabIndex = -1;
            close(entry);
            entry.toggle.addEventListener('click', () => {
                if (entry.shell.classList.contains('is-open')) close(entry, true);
                else open(entry);
            });
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) return;
            const trigger = event.target.closest('[data-open-nav-contact]');
            if (trigger) {
                const contact = entries.find(({ shell }) => shell.matches('[data-nav-contact]'));
                if (contact) {
                    event.preventDefault();
                    open(contact, trigger, true);
                }
            } else if (!nav.contains(event.target)) {
                close(openEntry());
            }
        });

        document.addEventListener('keydown', (event) => {
            const entry = openEntry();
            if (!entry) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                close(entry, true);
            } else if (event.key === 'Tab' && mobile.matches) {
                const targets = focusable(nav);
                const first = targets[0];
                const last = targets[targets.length - 1];
                if (event.shiftKey && (document.activeElement === first || !nav.contains(document.activeElement))) {
                    event.preventDefault();
                    last?.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first?.focus();
                }
            }
        });

        document.addEventListener('focusin', (event) => {
            if (nav.contains(event.target)) nav.classList.remove('is-nav-hidden');
            else if (!mobile.matches) close(openEntry());
        });
        mobile.addEventListener('change', syncMobile);
        let lastScrollY = window.scrollY;
        nav.classList.toggle('is-scrolled', lastScrollY > 50);
        window.addEventListener('scroll', () => {
            const currentY = Math.max(0, window.scrollY);
            nav.classList.toggle('is-scrolled', currentY > 50);
            const shouldHide = currentY > lastScrollY && currentY > 100 && !openEntry() && !nav.contains(document.activeElement);
            nav.classList.toggle('is-nav-hidden', shouldHide);
            lastScrollY = currentY;
        }, { passive: true });
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initNavbarMenus);
    else initNavbarMenus();
})();
