(() => {
    const grid = document.querySelector('.fabric-grid');
    if (!grid) return;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const compact = window.matchMedia('(max-width: 48rem)');
    const photos = [...grid.querySelectorAll('.fabric-grid__image')];
    const fabrics = [...grid.querySelectorAll('.fabric-grid__fabric')].map((element) => ({
        element,
        heading: element.querySelector('.fabric-grid__fabric-name'),
        height: 0,
    }));

    const photosByKitchen = new Map();
    grid.querySelectorAll('[data-kitchen-photo]').forEach((photo) => {
        const key = photo.dataset.kitchenPhoto;
        if (!photosByKitchen.has(key)) photosByKitchen.set(key, []);
        photosByKitchen.get(key).push(photo.querySelector('.fabric-grid__image'));
    });

    const kitchens = [...grid.querySelectorAll('[data-fabric-kitchen]')].map((lead) => ({
        body: lead.querySelector('.fabric-grid__details-body'),
        heading: lead.closest('.fabric-grid__fabric').querySelector('.fabric-grid__fabric-name'),
        photos: photosByKitchen.get(lead.dataset.fabricKitchen) || [],
        offset: 0,
        start: 0,
        end: 0,
        height: 0,
        top: 0,
    }));

    let frame = 0;
    let needsMeasure = true;

    const update = () => {
        frame = 0;
        const scrollY = window.scrollY;
        const motionOff = reduced.matches || compact.matches || Boolean(window.studioMotion?.paused);
        const photoOffsets = photos.map((photo) => {
            if (motionOff) return 0;
            const rect = photo.getBoundingClientRect();
            const progress = Math.max(0, Math.min(1, (window.innerHeight - rect.top) / (window.innerHeight + rect.height)));
            return (progress - 0.5) * rect.height * 0.06;
        });

        if (needsMeasure) {
            const style = getComputedStyle(grid);
            const stickyTop = parseFloat(style.getPropertyValue('--fabric-sticky-top')) || 80;
            const gap = parseFloat(style.columnGap) || 0;
            const headingHeights = fabrics.map((fabric) => fabric.heading.getBoundingClientRect().height);

            // Cache sticky text geometry before writing offsets.
            kitchens.forEach((kitchen) => {
                const rect = kitchen.body.getBoundingClientRect();
                kitchen.start = rect.top + scrollY - kitchen.offset;
                kitchen.height = rect.height;
                kitchen.end = Math.max(kitchen.start + rect.height, ...kitchen.photos.map((photo) => photo.getBoundingClientRect().bottom + scrollY));
                kitchen.top = Math.min(stickyTop + kitchen.heading.getBoundingClientRect().height + gap, window.innerHeight - rect.height - gap);
            });
            fabrics.forEach((fabric, index) => {
                if (fabric.height === headingHeights[index]) return;
                fabric.height = headingHeights[index];
                fabric.element.style.setProperty('--fabric-title-height', `${fabric.height}px`);
            });
            needsMeasure = false;
        }

        kitchens.forEach((kitchen) => {
            const offset = compact.matches ? 0 : Math.max(0, Math.min(scrollY + kitchen.top - kitchen.start, kitchen.end - kitchen.height - kitchen.start));
            if (Math.abs(offset - kitchen.offset) < 0.1) return;
            kitchen.body.style.translate = offset > 0 ? `0 ${offset}px` : '';
            kitchen.offset = offset;
        });
        photos.forEach((photo, index) => {
            photo.style.setProperty('--fabric-parallax-y', `${photoOffsets[index]}px`);
            photo.style.setProperty('--fabric-parallax-scale', motionOff ? '1' : '1.08');
        });
    };

    const schedule = () => {
        if (!frame) frame = window.requestAnimationFrame(update);
    };
    const measure = () => {
        needsMeasure = true;
        schedule();
    };

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', measure);
    window.addEventListener('pageshow', measure);
    reduced.addEventListener('change', schedule);
    compact.addEventListener('change', measure);
    window.studioMotion?.subscribe(schedule);
    grid.addEventListener('load', measure, true);

    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(measure);
        observer.observe(grid);
        grid.querySelectorAll('.fabric-grid__fabric, .fabric-grid__fabric-name, .fabric-grid__details-body, .fabric-grid__image').forEach((element) => observer.observe(element));
    }

    document.fonts?.ready.then(measure);
    measure();
})();
