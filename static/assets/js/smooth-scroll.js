(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    // keep native behavior on touch devices where inertial scroll is already smooth
    const hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    if (hasTouch) {
        return;
    }

    const getMaxScrollableY = () => {
        const root = document.documentElement;
        const body = document.body;

        if (!root || !body) {
            return Math.max(0, window.pageYOffset);
        }

        const contentHeight = Math.max(
            root.scrollHeight,
            root.offsetHeight,
            root.clientHeight,
            body.scrollHeight,
            body.offsetHeight,
            body.clientHeight
        );

        return Math.max(0, contentHeight - window.innerHeight);
    };

    const state = {
        currentY: window.pageYOffset,
        targetY: window.pageYOffset,
        maxY: getMaxScrollableY(),
        easing: 0.08,
        wheelStep: 1.1,
        isAnimating: false,
    };

    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const refreshBounds = () => {
        state.maxY = getMaxScrollableY();
        state.currentY = clamp(state.currentY, 0, state.maxY);
        state.targetY = clamp(state.targetY, 0, state.maxY);
    };

    const animate = () => {
        refreshBounds();

        const diff = state.targetY - state.currentY;

        if (Math.abs(diff) < 0.1) {
            state.currentY = state.targetY;
            window.scrollTo(0, state.currentY);
            state.isAnimating = false;
            return;
        }

        state.currentY += diff * state.easing;
        window.scrollTo(0, state.currentY);

        requestAnimationFrame(animate);
    };

    const startAnimation = () => {
        if (state.isAnimating) {
            return;
        }

        state.isAnimating = true;
        requestAnimationFrame(animate);
    };

    window.addEventListener('wheel', (event) => {
        event.preventDefault();

        refreshBounds();

        state.targetY = clamp(
            state.targetY + event.deltaY * state.wheelStep,
            0,
            state.maxY
        );

        startAnimation();
    }, { passive: false });

    // keep in sync with keyboard, scrollbar drag, anchor jumps and script-driven scrolls
    window.addEventListener('scroll', () => {
        refreshBounds();

        if (state.isAnimating) {
            return;
        }

        state.currentY = window.pageYOffset;
        state.targetY = window.pageYOffset;
    }, { passive: true });

    window.addEventListener('resize', () => {
        refreshBounds();
        startAnimation();
    }, { passive: true });

    window.addEventListener('load', refreshBounds, { passive: true });

    // recalc bounds when lazy-loaded media or dynamic blocks change page height
    if (typeof ResizeObserver === 'function') {
        const observer = new ResizeObserver(() => {
            refreshBounds();
        });

        observer.observe(document.documentElement);
        if (document.body) {
            observer.observe(document.body);
        }
    }
})();
