function initGallery(root) {
	if (root.dataset.galleryBound === 'true') {
		return
	}

	const openButtons = Array.from(root.querySelectorAll('[data-gallery-open]'))
	const overlay = root.querySelector('[data-gallery-overlay]')
	const viewport = root.querySelector('[data-gallery-overlay-viewport]')
	const closeTargets = Array.from(root.querySelectorAll('[data-gallery-close]'))
	const prevButton = root.querySelector('[data-gallery-prev]')
	const nextButton = root.querySelector('[data-gallery-next]')
	const OVERLAY_FADE_MS = 320

	if (!openButtons.length || !overlay || !viewport || typeof EmblaCarousel !== 'function') {
		return
	}

	const emblaApi = EmblaCarousel(viewport, {
		align: 'start',
		loop: openButtons.length > 1,
		dragFree: false,
		containScroll: false,
		duration: 36,
	})

	const syncActiveThumb = () => {
		const index = emblaApi.selectedScrollSnap()
		openButtons.forEach((button) => {
			const isActive = Number(button.dataset.index) === index
			button.setAttribute('aria-current', isActive ? 'true' : 'false')
		})
	}

	const openOverlay = (index) => {
		overlay.hidden = false
		overlay.classList.add('is-open')
		overlay.setAttribute('aria-hidden', 'false')
		document.documentElement.classList.add('is-gallery-overlay-open')
		emblaApi.scrollTo(index, true)
		syncActiveThumb()
	}

	const closeOverlay = () => {
		overlay.classList.remove('is-open')
		overlay.setAttribute('aria-hidden', 'true')
		document.documentElement.classList.remove('is-gallery-overlay-open')
		window.setTimeout(() => {
			if (!overlay.classList.contains('is-open')) {
				overlay.hidden = true
			}
		}, OVERLAY_FADE_MS)
	}

	openButtons.forEach((button) => {
		button.addEventListener('click', () => {
			openOverlay(Number(button.dataset.index) || 0)
		})
	})

	closeTargets.forEach((target) => {
		target.addEventListener('click', closeOverlay)
	})

	if (prevButton) {
		prevButton.addEventListener('click', () => emblaApi.scrollPrev())
	}

	if (nextButton) {
		nextButton.addEventListener('click', () => emblaApi.scrollNext())
	}

	overlay.addEventListener('click', (event) => {
		if (event.target === overlay) {
			closeOverlay()
		}
	})

	const onKeyDown = (event) => {
		if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
			closeOverlay()
		}
	}

	document.addEventListener('keydown', onKeyDown)
	emblaApi.on('select', syncActiveThumb)
	syncActiveThumb()

	root.dataset.galleryBound = 'true'
}

const initGalleries = () => {
	document.querySelectorAll('[data-gallery]').forEach((root) => initGallery(root))
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initGalleries)
} else {
	initGalleries()
}

document.addEventListener('swup:content:replace', initGalleries)
document.addEventListener('swup:page:view', initGalleries)

