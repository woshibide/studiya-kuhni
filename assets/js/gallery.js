(() => {
    const initGallery = (root) => {
        if (root.dataset.galleryBound === 'true') return;
        const buttons = Array.from(root.querySelectorAll('[data-gallery-open]'));
        const dialog = root.querySelector('[data-gallery-overlay]');
        const thumbnails = Array.from(root.querySelectorAll('[data-gallery-thumbnail]'));
        const images = [dialog?.querySelector('[data-gallery-image]'), dialog?.querySelector('[data-gallery-image-buffer]')];
        let image = images[0];
        const frame = dialog?.querySelector('[data-gallery-frame]');
        if (!buttons.length || !(dialog instanceof HTMLDialogElement) || !image || !frame || !thumbnails.length) return;

        const closeButton = dialog.querySelector('button[data-gallery-close]');
        const slot = dialog.querySelector('[data-gallery-slot]');
        const expand = dialog.querySelector('[data-gallery-expand]');
        const meta = dialog.querySelector('.gallery-overlay__meta-card');
        const error = dialog.querySelector('[data-gallery-error]');
        const shareStatus = dialog.querySelector('[data-gallery-share-status]');
        const shareLink = dialog.querySelector('[data-gallery-share-link]');
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
        const explorer = window.matchMedia('(min-width: 768px) and (hover: hover) and (pointer: fine)');
        const historyKey = `gallery-${Math.random().toString(36).slice(2)}`;
        let index = -1;
        let opener = null;
        let request = 0;
        let animation = 0;
        let tracking = false;
        let loaded = false;
        let closing = false;
        let closeTimer;
        let toastTimer;
        let toastRequest = 0;
        let opening = 0;
        let transitionReady = Promise.resolve();
        let current = { x: 50, y: 50 };
        let target = { x: 50, y: 50 };
        const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
        const instant = () => reduced.matches || Boolean(window.studioMotion?.paused);
        const list = root.querySelector('[data-gallery-list]');
        const scrollControls = root.querySelector('[data-gallery-scroll-controls]');
        const scrollPrev = root.querySelector('[data-gallery-scroll-prev]');
        const scrollNext = root.querySelector('[data-gallery-scroll-next]');
        if (list && scrollControls && scrollPrev && scrollNext) {
            const updateScrollControls = () => {
                const maxScroll = Math.max(0, list.scrollWidth - list.clientWidth);
                scrollPrev.disabled = list.scrollLeft <= 1;
                scrollNext.disabled = list.scrollLeft >= maxScroll - 1;
            };
            const scrollGallery = (direction) => list.scrollBy({
                left: direction * list.clientWidth * 0.85,
                behavior: instant() ? 'instant' : 'smooth',
            });
            scrollPrev.addEventListener('click', () => scrollGallery(-1));
            scrollNext.addEventListener('click', () => scrollGallery(1));
            list.addEventListener('scroll', updateScrollControls, { passive: true });
            const scrollObserver = new ResizeObserver(updateScrollControls);
            scrollObserver.observe(list);
            buttons.forEach((button) => scrollObserver.observe(button));
            scrollControls.hidden = false;
            updateScrollControls();
        }
        const urlFor = (selected) => {
            const url = new URL(window.location.href);
            url.searchParams.set('gallery', thumbnails[selected].dataset.galleryKey);
            return url;
        };
        const indexFromUrl = () => {
            const key = new URL(window.location.href).searchParams.get('gallery');
            return key === '' ? 0 : thumbnails.findIndex((thumbnail) => thumbnail.dataset.galleryKey === key);
        };
        const stopTracking = () => {
            tracking = false;
            cancelAnimationFrame(animation);
            animation = 0;
            dialog.classList.remove('is-exploring');
        };
        const positionFrame = () => {
            if (!dialog.open) return;
            if (meta) dialog.style.setProperty('--gallery-thumbnail-height', `${meta.getBoundingClientRect().height}px`);
            const bounds = slot.getBoundingClientRect();
            frame.style.setProperty('--gallery-photo-left', `${bounds.left}px`);
            frame.style.setProperty('--gallery-photo-top', `${bounds.top}px`);
            frame.style.setProperty('--gallery-photo-width', `${bounds.width}px`);
            frame.style.setProperty('--gallery-photo-height', `${bounds.height}px`);
        };
        const recenter = () => {
            stopTracking();
            current = { x: 50, y: 50 };
            target = { ...current };
            images.forEach((item) => { item.style.objectPosition = '50% 50%'; });
            positionFrame();
        };
        const delay = (duration) => new Promise((resolve) => window.setTimeout(resolve, instant() ? 0 : duration));
        const nextPaint = () => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
        const hideToast = () => {
            ++toastRequest;
            window.clearTimeout(toastTimer);
            shareStatus?.classList.remove('is-visible');
            if (shareLink) shareLink.hidden = true;
        };
        const reveal = async () => {
            const version = ++opening;
            await nextPaint();
            if (version === opening && dialog.open && !closing) dialog.classList.add('is-open');
        };
        const revealThumbnail = () => {
            const thumbnail = thumbnails[index];
            const strip = thumbnail.parentElement;
            const left = thumbnail.offsetLeft - strip.offsetLeft;
            if (left < strip.scrollLeft || left + thumbnail.offsetWidth > strip.scrollLeft + strip.clientWidth) {
                strip.scrollTo({ left: left - (strip.clientWidth - thumbnail.offsetWidth) / 2, behavior: 'instant' });
            }
        };
        const select = async (selected, { updateUrl = true } = {}) => {
            if (closing) return;
            const next = (selected + thumbnails.length) % thumbnails.length;
            if (next === index && loaded) return;
            index = next;
            loaded = false;
            const version = ++request;
            const thumbnail = thumbnails[index];
            stopTracking();
            hideToast();
            thumbnails.forEach((item, i) => item.setAttribute('aria-pressed', String(i === index)));
            if (updateUrl) history.replaceState(history.state, '', urlFor(index));
            revealThumbnail();
            frame.setAttribute('aria-busy', 'true');
            error.hidden = true;
            const preload = new Image();
            preload.decoding = 'async';
            try {
                await new Promise((resolve, reject) => {
                    preload.onload = resolve;
                    preload.onerror = reject;
                    preload.src = thumbnail.dataset.gallerySrc;
                });
                await preload.decode();
                await transitionReady;
                if (version !== request || !dialog.open || closing) return;
                const outgoing = image;
                const incoming = images.find((item) => item !== outgoing);
                incoming.classList.remove('is-current');
                incoming.classList.remove('is-underlay');
                incoming.src = preload.src;
                incoming.alt = thumbnail.querySelector('img').alt;
                incoming.width = preload.naturalWidth;
                incoming.height = preload.naturalHeight;
                await incoming.decode();
                if (version !== request || !dialog.open || closing) return;
                image = incoming;
                loaded = true;
                incoming.setAttribute('aria-hidden', 'false');
                outgoing.setAttribute('aria-hidden', 'true');
                slot.style.setProperty('--gallery-photo-ratio', `${preload.naturalWidth} / ${preload.naturalHeight}`);
                recenter();
                frame.setAttribute('aria-busy', 'false');
                // Keep the outgoing image opaque until its replacement has faded over it.
                outgoing.classList.add('is-underlay');
                outgoing.classList.remove('is-current');
                transitionReady = (async () => {
                    if (!instant()) await nextPaint();
                    if (!dialog.open || closing || image !== incoming) return;
                    incoming.classList.add('is-current');
                    await delay(420);
                    if (image !== outgoing) outgoing.classList.remove('is-underlay');
                })();
            } catch {
                if (version !== request || !dialog.open || closing) return;
                frame.setAttribute('aria-busy', 'false');
                error.hidden = false;
            }
        };
        const setExpanded = (expanded) => {
            if (expanded) dialog.classList.add('is-expanded');
            else dialog.classList.remove('is-expanded');
            expand.setAttribute('aria-pressed', String(expanded));
            expand.setAttribute('aria-label', expanded ? 'Свернуть фотографию' : 'Развернуть фотографию');
        };
        const open = (selected, { fromUrl = false, expanded = false, previewImage = null } = {}) => {
            window.clearTimeout(closeTimer);
            closing = false;
            dialog.classList.remove('is-closing');
            if (!dialog.open) {
                setExpanded(expanded);
                if (!fromUrl) history.pushState({ ...history.state, galleryEntry: historyKey }, '', urlFor(selected));
                dialog.hidden = false;
                dialog.showModal();
                document.documentElement.classList.add('is-gallery-overlay-open');
                image = images[0];
                images.forEach((item) => {
                    item.classList.remove('is-current');
                    item.classList.remove('is-underlay');
                });
                const preview = previewImage || buttons[selected].querySelector('img') || thumbnails[selected].querySelector('img');
                image.src = preview.currentSrc || preview.src;
                image.alt = preview.alt;
                image.width = preview.naturalWidth || Number(preview.getAttribute('width'));
                image.height = preview.naturalHeight || Number(preview.getAttribute('height'));
                image.setAttribute('aria-hidden', 'false');
                images[1].setAttribute('aria-hidden', 'true');
                image.classList.add('is-current');
                slot.style.setProperty('--gallery-photo-ratio', `${image.width} / ${image.height}`);
                positionFrame();
                // Reveal the cached inline photo first, then crossfade its decoded full-size source.
                image.decode().catch(() => {}).then(() => { if (dialog.open && !closing) reveal(); });
                closeButton.focus({ preventScroll: true });
            } else reveal();
            select(selected, { updateUrl: !fromUrl });
        };
        const close = ({ immediate = false } = {}) => {
            if (!dialog.open || closing) return;
            closing = true;
            ++request;
            ++opening;
            stopTracking();
            hideToast();
            dialog.classList.remove('is-open');
            dialog.classList.add('is-closing');
            if (instant() || immediate) dialog.close();
            else closeTimer = window.setTimeout(() => dialog.close(), 560);
        };
        buttons.forEach((button, selected) => button.addEventListener('click', () => {
            opener = button;
            open(selected);
        }));
        document.querySelectorAll('[data-gallery-layout-open], [data-gallery-hero-open]').forEach((button) => {
            const key = button.dataset.galleryLayoutOpen || button.dataset.galleryHeroOpen;
            const selected = thumbnails.findIndex((thumbnail) => thumbnail.dataset.galleryKey === key);
            if (selected < 0) return;
            button.addEventListener('click', (event) => {
                if (event.defaultPrevented) return;
                if (button.dataset.galleryLayoutOpen) {
                    const card = button.closest('[data-kuhnya-layout-card]');
                    if (!card?.classList.contains('is-expanded') || card.closest('.kuhnya-layout-grid')?.classList.contains('is-animating')) return;
                }
                event.preventDefault();
                event.stopPropagation();
                opener = button;
                open(selected, { expanded: true, previewImage: button.querySelector('img') });
            });
        });
        thumbnails.forEach((thumbnail, selected) => {
            thumbnail.addEventListener('click', () => select(selected));
            thumbnail.addEventListener('pointerleave', stopTracking);
            thumbnail.addEventListener('pointermove', (event) => {
                if (selected !== index || !loaded || !explorer.matches) return;
                const map = thumbnail.querySelector('.gallery-overlay__thumbnail-map');
                const bounds = map.getBoundingClientRect();
                const sourceRatio = image.naturalWidth / image.naturalHeight;
                const frameRatio = frame.clientWidth / frame.clientHeight;
                const width = bounds.width * Math.min(1, frameRatio / sourceRatio);
                const height = bounds.height * Math.min(1, sourceRatio / frameRatio);
                const left = clamp(event.clientX - bounds.left - width / 2, 0, bounds.width - width);
                const top = event.clientY - bounds.top - height / 2;
                const crop = map.querySelector('.gallery-overlay__crop');
                crop.style.width = `${width}px`;
                crop.style.height = `${height}px`;
                crop.style.transform = `translate(${left}px, ${top}px)`;
                target = {
                    x: bounds.width - width > 0.1 ? left / (bounds.width - width) * 100 : 50,
                    y: bounds.height - height > 0.1 ? clamp(top / (bounds.height - height) * 100, 0, 100) : 50,
                };
                tracking = true;
                dialog.classList.add('is-exploring');
                if (instant()) {
                    current = { ...target };
                    image.style.objectPosition = `${current.x}% ${current.y}%`;
                } else if (!animation) {
                    const tick = () => {
                        if (!tracking) { animation = 0; return; }
                        current.x += (target.x - current.x) * 0.07;
                        current.y += (target.y - current.y) * 0.07;
                        image.style.objectPosition = `${current.x}% ${current.y}%`;
                        if (Math.abs(target.x - current.x) + Math.abs(target.y - current.y) < 0.01) {
                            animation = 0;
                            return;
                        }
                        animation = requestAnimationFrame(tick);
                    };
                    animation = requestAnimationFrame(tick);
                }
            });
        });
        expand.addEventListener('click', () => {
            if (closing) return;
            setExpanded(!dialog.classList.contains('is-expanded'));
            recenter();
        });
        dialog.addEventListener('cancel', (event) => { event.preventDefault(); close(); });
        dialog.querySelector('[data-gallery-retry]').addEventListener('click', () => select(index));
        dialog.querySelectorAll('[data-gallery-close]').forEach((button) => button.addEventListener('click', close));
        dialog.addEventListener('click', (event) => {
            if (event.target.closest('[data-open-nav-contact]')) close({ immediate: true });
            else if (event.target === dialog) close();
        });
        dialog.addEventListener('close', () => {
            ++request;
            ++opening;
            window.clearTimeout(closeTimer);
            hideToast();
            closing = false;
            transitionReady = Promise.resolve();
            dialog.classList.remove('is-closing');
            setExpanded(false);
            index = -1;
            loaded = false;
            stopTracking();
            dialog.classList.remove('is-open');
            dialog.hidden = true;
            document.documentElement.classList.remove('is-gallery-overlay-open');
            if (indexFromUrl() >= 0) {
                if (history.state?.galleryEntry === historyKey) history.back();
                else {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('gallery');
                    history.replaceState(history.state, '', url);
                }
            }
            if (!document.querySelector('[data-nav-contact].is-open')) (opener || buttons[0]).focus({ preventScroll: true });
        });
        dialog.addEventListener('keydown', (event) => {
            if (closing || event.target.matches('input, textarea') || event.altKey || event.metaKey || event.ctrlKey) return;
            if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
                event.preventDefault();
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? thumbnails.length - 1 : index + (event.key === 'ArrowLeft' ? -1 : 1);
                select(next);
                if (event.target.matches('[data-gallery-thumbnail]')) thumbnails[index].focus({ preventScroll: true });
            }
        });
        const prev = dialog.querySelector('[data-gallery-prev]');
        const next = dialog.querySelector('[data-gallery-next]');
        prev.disabled = next.disabled = thumbnails.length < 2;
        prev.addEventListener('click', () => select(index - 1));
        next.addEventListener('click', () => select(index + 1));

        dialog.querySelector('[data-gallery-share]')?.addEventListener('click', async () => {
            if (closing || index < 0) return;
            const url = urlFor(index).href;
            hideToast();
            const version = toastRequest;
            try {
                await navigator.clipboard.writeText(url);
                if (!dialog.open || closing || version !== toastRequest) return;
                shareStatus.textContent = 'Ссылка скопирована.';
                shareStatus.classList.add('is-visible');
                toastTimer = window.setTimeout(() => shareStatus.classList.remove('is-visible'), 2400);
            } catch {
                if (!dialog.open || closing || version !== toastRequest) return;
                shareStatus.textContent = 'Скопируйте ссылку ниже';
                shareStatus.classList.add('is-visible');
                shareLink.value = url;
                shareLink.hidden = false;
                shareLink.focus();
                shareLink.select();
            }
        });
        const syncUrl = () => {
            const selected = indexFromUrl();
            if (selected >= 0) open(selected, { fromUrl: true });
            else close();
        };
        window.addEventListener('popstate', syncUrl);
        window.addEventListener('resize', recenter);
        dialog.addEventListener('scroll', () => {
            frame.classList.add('is-following-scroll');
            positionFrame();
            frame.getBoundingClientRect();
            frame.classList.remove('is-following-scroll');
        });
        const layoutObserver = new ResizeObserver(positionFrame);
        layoutObserver.observe(slot);
        if (meta) layoutObserver.observe(meta);
        explorer.addEventListener('change', recenter);
        reduced.addEventListener('change', stopTracking);
        document.addEventListener('visibilitychange', stopTracking);
        root.dataset.galleryBound = 'true';
        syncUrl();
    };
    const init = () => document.querySelectorAll('[data-gallery]').forEach(initGallery);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
