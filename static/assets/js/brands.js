const brandJson = [
	{ file: 'fabrics-aranCucine.png', name: 'aranCucine' },
	{ file: 'fabrics-aster.png', name: 'aster' },
	{ file: 'fabrics-homeCucine.png', name: 'homeCucine' },
	{ file: 'fabrics-kitchenAid.png', name: 'kitchenAid' },
	{ file: 'fabrics-lottocento.png', name: 'lottocento' },
	{ file: 'fabrics-lubiex.png', name: 'lubiex' },
	{ file: "fabrics-Kuppersbuch.png", name: "kuppersbuch"},
	{ file: 'fabrics-miele.png', name: 'miele' },
	{ file: 'fabrics-mossman.png', name: 'mossman' },
	{ file: 'fabrics-neff.png', name: 'neff' },
	{ file: 'fabrics-nolte.png', name: 'nolte' },
	{ file: 'fabrics-scavolini.png', name: 'scavolini' },
	{ file: 'fabrics-smeg.png', name: 'smeg' }
];

const brandNameRemap = {
	aranCucine: 'Aran Cucine',
	aster: 'Aster',
	homeCucine: 'Home Cucine',
	kitchenAid: 'KitchenAid',
	lottocento: 'Lottocento',
	lubiex: 'Lubiex',
	kuppersbuch: 'Küppersbuch',
	miele: 'Miele',
	mossman: 'Mossman',
	neff: 'Neff',
	nolte: 'Nolte',
	scavolini: 'Scavolini',
	smeg: 'Smeg'
};

const brandBasePath = '/assets/brands/fabrics/png-white/';

const formatFallbackName = (value) => {
	return value
		.replace(/([a-z])([A-Z])/g, '$1 $2')
		.replace(/[-_]/g, ' ')
		.replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const buildMarquee = () => {
	const section = document.querySelector('#brands');
	if (!section) {
		return;
	}

	const wrapper = section.querySelector('[data-marquee-wrapper]');
	if (!wrapper) {
		return;
	}

	const rows = Array.from({ length: 3 }, () => []);
	brandJson.forEach((brand, index) => {
		rows[index % rows.length].push(brand);
	});

	const createBrandItem = (brand) => {
		const item = document.createElement('div');
		item.className = 'brand-item';

		const figure = document.createElement('figure');

		const displayName = brandNameRemap[brand.name] || formatFallbackName(brand.name);
		const img = document.createElement('img');
		img.src = `${brandBasePath}${brand.file}`;
		img.alt = displayName;
		img.loading = 'lazy';

		const caption = document.createElement('figcaption');
		caption.className = 'brand-label';
		caption.textContent = displayName;

		figure.appendChild(img);
		figure.appendChild(caption);
		item.appendChild(figure);

		return item;
	};

	const createMarqueeRow = (items, reversed, isDuplicate) => {
		const marquee = document.createElement('div');
		marquee.className = `marquee${isDuplicate ? ' marquee--duplicate' : ''}`;
		marquee.dataset.reversed = reversed;

		for (let i = 0; i < 3; i += 1) {
			const content = document.createElement('div');
			content.className = 'marquee-content';
			if (i > 0) {
				content.setAttribute('aria-hidden', 'true');
			}

			items.forEach((brand) => {
				content.appendChild(createBrandItem(brand));
			});

			marquee.appendChild(content);
		}

		return marquee;
	};

	const directions = ['false', 'true', 'false'];
	const duplicateDirections = ['true', 'false', 'true'];

	rows.forEach((rowBrands, index) => {
		if (!rowBrands.length) {
			return;
		}

		wrapper.appendChild(createMarqueeRow(rowBrands, directions[index], false));

		const shuffled = [...rowBrands].sort(() => 0.5 - Math.random());
		wrapper.appendChild(createMarqueeRow(shuffled, duplicateDirections[index], true));
	});

	if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
		return;
	}

	const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
	const phoneQuery = window.matchMedia('(max-width: 48rem)');
	if (reducedMotionQuery.matches || phoneQuery.matches) {
		return;
	}

	gsap.registerPlugin(ScrollTrigger);

	let direction = 1;
	const duration = 50;
	const marquees = wrapper.querySelectorAll('.marquee');

	if (!marquees.length) {
		return;
	}

	const tl = gsap.timeline({
		repeat: -1,
		yoyo: false,
		onReverseComplete() {
			this.totalTime(this.rawTime() + this.duration() * 10);
		}
	});

	marquees.forEach((marquee) => {
		const content = marquee.querySelectorAll('.marquee-content');
		if (!content.length) {
			return;
		}

		tl.to(content, {
			xPercent: marquee.dataset.reversed === 'true' ? -100 : 100,
			repeat: 0,
			ease: 'linear',
			duration: duration
		}, '<');
	});

	ScrollTrigger.create({
		trigger: section,
		onUpdate(self) {
			if (self.direction !== direction) {
				direction *= -1;
			}

			tl.timeScale(duration * self.getVelocity() / 4000);
			gsap.to(tl, { timeScale: direction, overwrite: true });
		}
	});
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', buildMarquee);
} else {
	buildMarquee();
}