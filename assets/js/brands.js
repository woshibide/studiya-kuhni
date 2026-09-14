const initBrandsMarquee = () => {
    const section = document.querySelector('#brands');
    if (!section || section.dataset.marqueeInitialized === 'true') return;

    const { gsap, ScrollTrigger } = window;
    if (!gsap || !ScrollTrigger) {
        if (!section.dataset.marqueeRetryBound) {
            section.dataset.marqueeRetryBound = 'true';
            window.addEventListener('load', initBrandsMarquee, { once: true });
        }
        return;
    }

    const marquees = Array.from(section.querySelectorAll('.marquee'));
    if (!marquees.length) return;
    section.dataset.marqueeInitialized = 'true';
    gsap.registerPlugin(ScrollTrigger);

    const duration = 50;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let direction = 1;
    const timeline = gsap.timeline({
        paused: true,
        repeat: -1,
        yoyo: false,
        onReverseComplete() {
            this.totalTime(this.rawTime() + this.duration() * 10);
        }
    });

    const isPaused = () => window.studioMotion?.paused || reducedMotion.matches || section.contains(document.activeElement);
    const syncMotion = () => {
        if (isPaused()) {
            gsap.killTweensOf(timeline);
            timeline.timeScale(direction).pause();
        } else {
            timeline.paused(false);
        }
    };

    const buildRows = () => {
        const time = timeline.totalTime();
        timeline.clear();
        marquees.forEach((marquee) => {
            const track = marquee.querySelector('.marquee-track');
            const original = track?.querySelector('.marquee-content');
            if (!original || !marquee.clientWidth) return;

            // Keep one block on either side, including on wide screens or short CMS lists.
            const copies = Math.max(3, Math.ceil(marquee.clientWidth / original.offsetWidth) + 2);
            while (track.children.length > copies) track.lastElementChild.remove();
            while (track.children.length < copies) {
                const clone = original.cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                clone.inert = true;
                track.appendChild(clone);
            }

            const reversed = marquee.dataset.direction === 'rtl';
            // Offset right-moving rows by one block so their leading edge never opens a gap.
            timeline.fromTo(track.children, {
                xPercent: reversed ? 0 : -100
            }, {
                xPercent: reversed ? -100 : 0,
                duration,
                ease: 'linear',
                repeat: 0,
                immediateRender: true
            }, 0);
        });
        timeline.totalTime(time);
        syncMotion();
    };

    buildRows();
    timeline.timeScale(1);
    ScrollTrigger.create({
        trigger: section,
        onUpdate(self) {
            direction = self.direction;
            if (isPaused()) {
                gsap.killTweensOf(timeline);
                timeline.timeScale(direction);
                return;
            }
            timeline.timeScale(duration * self.getVelocity() / 4000);
            gsap.to(timeline, {
                timeScale: direction,
                duration: 0.5,
                ease: 'power1.out',
                overwrite: true
            });
        }
    });

    // Rebuild only when geometry changes; scroll updates never rebuild the loop.
    const resizeObserver = new ResizeObserver(() => {
        buildRows();
        ScrollTrigger.refresh();
    });
    marquees.forEach((marquee) => resizeObserver.observe(marquee));
    section.addEventListener('focusin', syncMotion);
    section.addEventListener('focusout', () => queueMicrotask(syncMotion));
    window.studioMotion?.subscribe(syncMotion);
    reducedMotion.addEventListener('change', syncMotion);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBrandsMarquee);
} else {
    initBrandsMarquee();
}
