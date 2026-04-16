const clamp = (value, min, max) => Math.min(Math.max(value, min), max)

const initArchivePostParallax = () => {
	const cover = document.querySelector('[data-archive-post-cover]')
	const image = document.querySelector('[data-archive-post-cover-image]')
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
	const isPhone = window.matchMedia('(max-width: 48rem)').matches

	if (!cover || !image || reducedMotion || isPhone) {
		return
	}

	let ticking = false

	const update = () => {
		const coverRect = cover.getBoundingClientRect()
		const viewportHeight = window.innerHeight || document.documentElement.clientHeight
		const progress = clamp((viewportHeight - coverRect.top) / (viewportHeight + coverRect.height), 0, 1)
		const parallaxY = (progress - 0.5) * 60
		const scale = 1.02 + progress * 0.05

		image.style.transform = `translate3d(0, ${parallaxY}px, 0) scale(${scale})`
		ticking = false
	}

	const onScroll = () => {
		if (ticking) {
			return
		}

		ticking = true
		window.requestAnimationFrame(update)
	}

	update()
	window.addEventListener('scroll', onScroll, { passive: true })
	window.addEventListener('resize', onScroll)
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initArchivePostParallax)
} else {
	initArchivePostParallax()
}
