const initBrandsMarquee = () => {
	const section = document.querySelector('#brands');
	if (!section) {
		return;
	}

	if (typeof window.gsapMarquee !== 'function') {
		if (!section.dataset.marqueeRetryBound) {
			section.dataset.marqueeRetryBound = 'true';
			window.addEventListener('load', initBrandsMarquee, { once: true });
		}
		return;
	}

	if (section.dataset.marqueeInitialized === 'true') {
		return;
	}

	section.dataset.marqueeInitialized = 'true';

	const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
	const marquees = Array.from(section.querySelectorAll('.marquee'));
	if (!marquees.length) {
		return;
	}

	const start = () => {
		marquees.forEach((marquee) => {
			const speed = Number(marquee.dataset.speed) || 100;
			const direction = marquee.dataset.direction === 'rtl' ? 'rtl' : 'ltr';

			window.gsapMarquee(marquee, {
				speed,
				direction,
				pauseOnHover: true,
				pauseOnClick: false,
				variableSpeed: false,
				startPaused: reducedMotionQuery.matches,
				responsive: true,
				loop: true,
				containerSelector: '.marqueeInner',
				itemsSelector: '.marquee-item'
			});
		});
	};

	const runAfterLayout = () => {
		setTimeout(start, 0);
	};

	if (window.imagesLoaded) {
		window.imagesLoaded(section, runAfterLayout);
		return;
	}

	runAfterLayout();
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initBrandsMarquee);
} else {
	initBrandsMarquee();
}