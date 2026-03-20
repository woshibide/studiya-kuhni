function initGallery(root) {
	const mainImg = root.querySelector('[data-gallery-main-img]')
	const thumbs = Array.from(root.querySelectorAll('[data-gallery-thumb]'))
	const emblaRoot = root.querySelector('[data-gallery-embla]')
	const emblaViewport = root.querySelector('[data-gallery-embla-viewport]')
	const shouldAutoplay = root.dataset.galleryAutoplay === 'true'
	const debug = root.dataset.galleryDebug === 'false'

	if (debug) {
		console.log('[gallery] init', {
			autoplay: shouldAutoplay,
			count: Number(root.dataset.galleryCount || thumbs.length),
			hasEmbla: typeof EmblaCarousel,
			hasAutoplayPlugin: typeof EmblaCarouselAutoplay,
			hasEmblaRoot: Boolean(emblaRoot),
			hasEmblaViewport: Boolean(emblaViewport),
		})
	}

	if (!mainImg || thumbs.length === 0) return

	const setActive = (index) => {
		thumbs.forEach((btn) => {
			const isActive = Number(btn.dataset.index) === index
			if (isActive) btn.setAttribute('aria-current', 'true')
			else btn.removeAttribute('aria-current')
		})

		const activeButton = thumbs.find((btn) => Number(btn.dataset.index) === index)
		const activeImg = activeButton ? activeButton.querySelector('img') : null
		if (!activeImg) return

		mainImg.src = activeImg.src
		mainImg.alt = activeImg.alt || ''
	}

	thumbs.forEach((btn) => {
		btn.addEventListener('click', () => {
			const index = Number(btn.dataset.index)
			if (debug) console.log('[gallery] thumb click', { index })
			setActive(index)
			if (emblaApi) emblaApi.scrollTo(index)
		})
	})

	setActive(0)

	let emblaApi = null
	if (emblaViewport && typeof EmblaCarousel === 'function') {
		try {
			const plugins = []
			if (shouldAutoplay && typeof EmblaCarouselAutoplay === 'function') {
				plugins.push(EmblaCarouselAutoplay({
					delay: 6000,
					stopOnInteraction: false,
					stopOnMouseEnter: false,
				}))
			}

			const options = {
				align: 'center',
				loop: shouldAutoplay,
				dragFree: !shouldAutoplay,
				containScroll: shouldAutoplay ? false : 'trimSnaps',
				duration: shouldAutoplay ? 80 : 25,
			}

			emblaApi = EmblaCarousel(emblaViewport, options, plugins)
			if (debug) console.log('[gallery] snaps', { count: emblaApi.scrollSnapList().length })

			emblaApi.on('select', () => setActive(emblaApi.selectedScrollSnap()))
			if (debug) console.log('[gallery] embla ready', { autoplay: shouldAutoplay })
		} catch (error) {
			if (debug) console.error('[gallery] embla init failed', error)
		}
	} else {
		if (debug) console.warn('[gallery] embla not initialized')
	}
}

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('[data-gallery]').forEach((root) => initGallery(root))
})

