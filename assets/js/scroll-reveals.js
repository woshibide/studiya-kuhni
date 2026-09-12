(() => {
    const gsap = window.gsap;
    if (!gsap || !('IntersectionObserver' in window)) return;

    // Semantic copy covers CMS-authored content as well as template headings.
    // Component selectors define larger reading units and media independently.
    // New components can opt in with data-scroll-reveal, or opt out with "off".
    const selector = [
        'main [data-scroll-reveal]:not([data-scroll-reveal="off"])',
        'main :is(h1, h2, h3, h4, h5, h6, p, blockquote)',
        'main section > :is(ul, ol):not([class])',
        'main .studio-rich-text > *',
        'main .section-sticky > .primary-btn',
        'main .section-sticky-content > .primary-btn',
        'main .hero__cta-wrapper',
        'main .big-message__text',
        'main #brands .marquee',
        'main .cta-media',
        'main .cta-warmup-media',
        'main .benefits-container > figure > img',
        'main [data-home-fabric-row]',
        'main .fabric-card__header',
        'main .fabric-grid__item > .fabric-card',
        'main .other-fabrics-section .fabric-grid > figure',
        'main .kuhnya-card-overview__content > a',
        'main .kuhnya-card-overview__facts > li',
        'main .kuhnya-card-overview__media',
        'main .fabric-info__inner > :not(.fabric-info__text)',
        'main .kuhnya-features-list__item',
        'main .kuhnya-layout-card',
        'main .gallery__intro',
        'main .gallery-inline__item',
        'main .gallery-inline__navigation',
        'main .faq-category-tabs',
        'main .faq-question-item',
        'main .archive-posts-card-link',
        'main .archive-posts-card figcaption > *',
        'main .archive-post-intro > *',
        'main .archive-post-blocks > *',
        'main .privacy-content > *',
        'main .contacts-block > :is(form, address, a, .contacts-links)',
        'main .designers-proof__intro > *',
        'main .designers-proof-card > *',
        'main .mediakit-section__content > :not(.mediakit-downloads)',
        'main .mediakit-downloads__item',
        'main .proizvodstvo-image',
        '#footer-typed-line',
        '#footer-details > .info > *',
        '#footer-details > .links > ul',
    ].join(',');
    const excluded = [
        '[data-scroll-reveal="off"]', 'dialog', '[role="dialog"]',
        '[aria-hidden="true"]', '.sr-only', 'script', 'style',
        '.kunya-sticky-overview', '.fabric-info__map-wrap',
    ].join(',');
    // Photo-bearing cards must also stay opaque: fading a parent fades its image.
    // Include CSS-backed catalogue photos as well as ordinary image elements.
    const mediaSelector = [
        'img', 'picture', 'video', 'svg', 'canvas',
        '[data-scroll-reveal="media"]', '[style*="background-image"]',
        '.home-fabric-media', '.fabric-card__media', '.kuhnya-layout-media',
    ].join(',');
    const seen = new WeakSet();
    const media = gsap.matchMedia();

    media.add('(prefers-reduced-motion: no-preference)', () => {
        const candidates = [...document.querySelectorAll(selector)]
            .filter((element) => !element.closest(excluded) && !element.matches('.studio-rich-text'));
        const candidateSet = new Set(candidates);
        const targets = candidates.filter((element) => {
            for (let parent = element.parentElement; parent; parent = parent.parentElement) {
                if (candidateSet.has(parent)) return false;
            }
            return !seen.has(element);
        });
        const active = new Map();
        const mediaTargets = new Set(targets.filter((element) =>
            element.matches(mediaSelector) || element.querySelector(mediaSelector)));
        const compact = window.matchMedia('(max-width: 48rem)');
        const finish = (element) => {
            seen.add(element);
            observer.unobserve(element);
            const animation = active.get(element);
            active.delete(element);
            // Restore original styles so hover effects and layout retain ownership.
            if (animation) {
                animation.tween.revert();
                // Keep interaction independent of deferred GSAP style cleanup.
                animation.styles.forEach(([property, value, priority]) => {
                    if (value) element.style.setProperty(property, value, priority);
                    else element.style.removeProperty(property);
                });
            }
        };
        const finishActive = () => [...active.keys()].forEach(finish);
        const observer = new IntersectionObserver((entries) => {
            const groups = new Map();
            entries.forEach(({ target, isIntersecting, boundingClientRect }) => {
                if (!isIntersecting || seen.has(target)) return;
                // Hidden tabs remain untouched until displayed. Focus and restored
                // scroll positions always get fully readable content immediately.
                if (target.closest('[hidden], [inert]')) return;
                if (document.hidden || target.contains(document.activeElement)
                    || boundingClientRect.top < window.innerHeight * 0.2) {
                    finish(target);
                    return;
                }
                seen.add(target);
                observer.unobserve(target);
                const group = target.parentElement;
                const index = groups.get(group) || 0;
                groups.set(group, index + 1);
                const styles = ['transform', 'opacity', 'translate', 'rotate', 'scale'].map((property) =>
                    [property, target.style.getPropertyValue(property), target.style.getPropertyPriority(property)]);
                const tween = gsap.from(target, {
                    y: compact.matches ? 10 : 16,
                    ...(mediaTargets.has(target) ? {} : { opacity: 0.4 }),
                    duration: compact.matches ? 0.5 : 0.65,
                    delay: Math.min(index * 0.045, 0.135),
                    ease: 'power2.out',
                    onComplete: () => finish(target),
                });
                active.set(target, { tween, styles });
            });
        }, { rootMargin: '0px 0px -24px 0px', threshold: 0 });

        // Read layout once, before animation writes. Never conceal content in CSS:
        // missing GSAP, disabled JavaScript and printing all retain complete pages.
        const initial = targets.map((element) => ({ element, rect: element.getBoundingClientRect() }));
        initial.forEach(({ element, rect }) => {
            if (rect.height > 0 && rect.top < window.innerHeight) seen.add(element);
            else observer.observe(element);
        });

        const interact = (event) => {
            targets.forEach((element) => {
                if (element.contains(event.target)) finish(element);
            });
        };
        const restore = (event) => {
            if (event.persisted) {
                observer.disconnect();
                finishActive();
            }
        };
        document.addEventListener('focusin', interact);
        document.addEventListener('pointerdown', interact, { passive: true });
        document.addEventListener('click', interact);
        document.addEventListener('visibilitychange', finishActive);
        window.addEventListener('pagehide', finishActive);
        window.addEventListener('pageshow', restore);
        window.addEventListener('beforeprint', finishActive);

        return () => {
            observer.disconnect();
            finishActive();
            document.removeEventListener('focusin', interact);
            document.removeEventListener('pointerdown', interact);
            document.removeEventListener('click', interact);
            document.removeEventListener('visibilitychange', finishActive);
            window.removeEventListener('pagehide', finishActive);
            window.removeEventListener('pageshow', restore);
            window.removeEventListener('beforeprint', finishActive);
        };
    });
})();
