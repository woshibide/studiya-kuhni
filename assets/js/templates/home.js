const heroTyped = document.querySelector('#home-hero-typed')
const heroCaret = document.querySelector('#home-hero-caret')

if (heroTyped) {
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
	const wordsSource = heroTyped.dataset.words ?? '[]'
	let words = []

	try {
		const parsed = JSON.parse(wordsSource)
		words = Array.isArray(parsed)
			? parsed.map((word) => String(word).trim()).filter(Boolean)
			: []
	} catch (error) {
		words = []
	}

	if (words.length === 0) {
		words = [heroTyped.textContent.trim() || 'тебя']
	}

	const TYPE_DELAY_MS = 95
	const ERASE_DELAY_MS = 55
	const FULL_WORD_PAUSE_MS = 1200
	const EMPTY_WORD_PAUSE_MS = 260

	const wait = (ms) => new Promise((resolve) => {
		window.setTimeout(resolve, ms)
	})

	const blinkCaret = () => {
		if (!heroCaret || reducedMotion) {
			return null
		}

		const intervalId = window.setInterval(() => {
			heroCaret.classList.toggle('is-hidden')
		}, 520)

		return intervalId
	}

	if (reducedMotion || words.length === 1) {
		heroTyped.textContent = words[0]
		if (heroCaret) {
			heroCaret.classList.add('is-hidden')
		}
	} else {
		const caretIntervalId = blinkCaret()

		const typeWord = async (word) => {
			for (let index = 1; index <= word.length; index += 1) {
				heroTyped.textContent = word.slice(0, index)
				await wait(TYPE_DELAY_MS)
			}
		}

		const eraseWord = async (word) => {
			for (let index = word.length - 1; index >= 0; index -= 1) {
				heroTyped.textContent = word.slice(0, index)
				await wait(ERASE_DELAY_MS)
			}
		}

		const cycle = async () => {
			let wordIndex = 0

			while (true) {
				const activeWord = words[wordIndex]
				await typeWord(activeWord)
				await wait(FULL_WORD_PAUSE_MS)
				await eraseWord(activeWord)
				await wait(EMPTY_WORD_PAUSE_MS)
				wordIndex = (wordIndex + 1) % words.length
			}
		}

		cycle()

		window.addEventListener('beforeunload', () => {
			if (caretIntervalId) {
				window.clearInterval(caretIntervalId)
			}
		})
	}
}

const HOME_FABRICS_SELECTOR = '#fabrics-intro .fabrics-listing'
const HOME_FABRIC_CARD_SELECTOR = '[data-home-fabric-card]'
const HOME_FABRIC_ROW_SELECTOR = '[data-home-fabric-row]'
const HOME_FABRIC_MEDIA_SELECTOR = '.home-fabric-media'
const HOME_FABRIC_KITCHEN_LINK_SELECTOR = 'a[data-fabric-image]'
const HOME_FABRIC_TOGGLE_SELECTOR = '[data-home-fabric-toggle]'
const HOME_EXPANDED_COL_START = '1'
const HOME_EXPANDED_COL_SPAN = '12'
const HOME_AUTOPLAY_INTERVAL_MS = 4600
const HOME_AUTOPLAY_STAGGER_MS = 520
const HOME_IMAGE_FADE_OUT_MS = 180
const homeReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
const homeCardState = new WeakMap()
let homeFabricsParallaxCleanup = null

const clamp = (value, min, max) => Math.min(Math.max(value, min), max)

const getHomeCardState = (card) => {
	if (homeCardState.has(card)) {
		return homeCardState.get(card)
	}

	const links = Array.from(card.querySelectorAll(HOME_FABRIC_KITCHEN_LINK_SELECTOR))
	const state = {
		links,
		activeIndex: links.length ? 0 : -1,
		isPointerInside: false,
		isFocusInside: false,
		autoplayStartTimeoutId: null,
		autoplayIntervalId: null,
		imageSwapTimeoutId: null,
	}

	homeCardState.set(card, state)
	return state
}

const clearHomeAutoplay = (card) => {
	const state = getHomeCardState(card)
	if (state.autoplayStartTimeoutId) {
		window.clearTimeout(state.autoplayStartTimeoutId)
		state.autoplayStartTimeoutId = null
	}

	if (state.autoplayIntervalId) {
		window.clearInterval(state.autoplayIntervalId)
		state.autoplayIntervalId = null
	}
}

const setHomeFabricImage = (card, imageUrl, options = {}) => {
	const { animate = true } = options
	const media = card.querySelector(HOME_FABRIC_MEDIA_SELECTOR)
	if (!media || !imageUrl) {
		return
	}
	const state = getHomeCardState(card)

	if (media.dataset.currentImage === imageUrl) {
		return
	}

	if (!animate || homeReducedMotion) {
		media.style.backgroundImage = `url("${imageUrl.replace(/"/g, '\\"')}")`
		media.dataset.currentImage = imageUrl
		media.classList.remove('is-image-fading')
		if (state.imageSwapTimeoutId) {
			window.clearTimeout(state.imageSwapTimeoutId)
			state.imageSwapTimeoutId = null
		}
		return
	}

	if (state.imageSwapTimeoutId) {
		window.clearTimeout(state.imageSwapTimeoutId)
	}

	media.classList.add('is-image-fading')
	state.imageSwapTimeoutId = window.setTimeout(() => {
		media.style.backgroundImage = `url("${imageUrl.replace(/"/g, '\\"')}")`
		media.dataset.currentImage = imageUrl
		window.requestAnimationFrame(() => {
			media.classList.remove('is-image-fading')
		})
		state.imageSwapTimeoutId = null
	}, HOME_IMAGE_FADE_OUT_MS)
}

const resetHomeFabricImage = (card) => {
	setHomeFabricImage(card, card.dataset.defaultImage || '/assets/placeholder.svg', { animate: false })
}

const setHomeActiveKitchenLink = (card, activeLink) => {
	const state = getHomeCardState(card)
	const links = state.links
	links.forEach((link) => {
		const isActive = link === activeLink
		link.classList.toggle('is-active', isActive)
		if (isActive) {
			link.setAttribute('aria-current', 'true')
		} else {
			link.removeAttribute('aria-current')
		}
	})

	const nextIndex = links.indexOf(activeLink)
	if (nextIndex >= 0) {
		state.activeIndex = nextIndex
	}
}

const resetHomeActiveKitchenLink = (card) => {
	const state = getHomeCardState(card)
	const defaultLink = state.links[0] || null
	setHomeActiveKitchenLink(card, defaultLink)
}

const setHomeCardByIndex = (card, index, options = {}) => {
	const { animate = true } = options
	const state = getHomeCardState(card)
	if (!state.links.length) {
		return
	}

	const normalized = ((index % state.links.length) + state.links.length) % state.links.length
	const targetLink = state.links[normalized]
	setHomeActiveKitchenLink(card, targetLink)
	setHomeFabricImage(card, targetLink.dataset.fabricImage || card.dataset.defaultImage || '/assets/placeholder.svg', { animate })
}

const shouldHomeCardAutoplay = (card) => {
	const state = getHomeCardState(card)
	if (state.links.length <= 1) {
		return false
	}

	if (state.isPointerInside || state.isFocusInside) {
		return false
	}

	if (card.classList.contains('is-expanded')) {
		return false
	}

	return true
}

const advanceHomeCardAutoplay = (card) => {
	if (!shouldHomeCardAutoplay(card)) {
		return
	}

	const state = getHomeCardState(card)
	const nextIndex = state.activeIndex + 1
	setHomeCardByIndex(card, nextIndex)
}

const setupHomeCardAutoplay = (card, order) => {
	clearHomeAutoplay(card)
	const state = getHomeCardState(card)
	if (state.links.length <= 1) {
		return
	}

	state.autoplayStartTimeoutId = window.setTimeout(() => {
		advanceHomeCardAutoplay(card)
		state.autoplayIntervalId = window.setInterval(() => {
			advanceHomeCardAutoplay(card)
		}, HOME_AUTOPLAY_INTERVAL_MS)
	}, order * HOME_AUTOPLAY_STAGGER_MS)
}

const initHomeFabricsParallax = (listing) => {
	if (homeFabricsParallaxCleanup) {
		homeFabricsParallaxCleanup()
		homeFabricsParallaxCleanup = null
	}

	if (homeReducedMotion || !listing) {
		return
	}

	const mediaItems = Array.from(listing.querySelectorAll(HOME_FABRIC_MEDIA_SELECTOR))
	if (!mediaItems.length) {
		return
	}

	let ticking = false

	const update = () => {
		const viewportHeight = window.innerHeight || document.documentElement.clientHeight

		mediaItems.forEach((media) => {
			const mediaRect = media.getBoundingClientRect()
			const progress = clamp((viewportHeight - mediaRect.top) / (viewportHeight + mediaRect.height), 0, 1)
			const parallaxY = (progress - 0.5) * 80
			const parallaxScale = progress * 0.05

			media.style.setProperty('--home-fabric-parallax-y', `${parallaxY.toFixed(2)}px`)
			media.style.setProperty('--home-fabric-parallax-scale', parallaxScale.toFixed(4))
		})

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

	homeFabricsParallaxCleanup = () => {
		window.removeEventListener('scroll', onScroll)
		window.removeEventListener('resize', onScroll)
	}
}

const setStyleProperty = (element, property, value) => {
	if (value && value !== '') {
		element.style.setProperty(property, value)
		return
	}

	element.style.removeProperty(property)
}

const ensureHomeCardOriginalLayout = (card) => {
	if (card.dataset.originalColStart === undefined) {
		card.dataset.originalColStart = card.style.getPropertyValue('--col-start').trim()
	}

	if (card.dataset.originalColSpan === undefined) {
		card.dataset.originalColSpan = card.style.getPropertyValue('--col-span').trim()
	}

	if (card.dataset.originalMarginTop === undefined) {
		card.dataset.originalMarginTop = card.style.getPropertyValue('margin-top').trim()
	}
}

const setHomeCardToggleState = (card, isExpanded) => {
	const toggle = card.querySelector(HOME_FABRIC_TOGGLE_SELECTOR)
	if (!toggle) {
		return
	}

	toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false')
	toggle.setAttribute('aria-label', isExpanded ? 'Minimize fabric card' : 'Expand fabric card')
	toggle.textContent = isExpanded ? '-' : '+'
}

const applyHomeCardLayout = (card, mode) => {
	ensureHomeCardOriginalLayout(card)

	if (mode === 'expanded') {
		setStyleProperty(card, '--col-start', HOME_EXPANDED_COL_START)
		setStyleProperty(card, '--col-span', HOME_EXPANDED_COL_SPAN)
		setStyleProperty(card, 'margin-top', '0')
		return
	}

	setStyleProperty(card, '--col-start', card.dataset.originalColStart)
	setStyleProperty(card, '--col-span', card.dataset.originalColSpan)
	setStyleProperty(card, 'margin-top', card.dataset.originalMarginTop)
}

const animateHomeRowLayout = (row, mutateLayout) => {
	const rowCards = Array.from(row.querySelectorAll(HOME_FABRIC_CARD_SELECTOR))
	if (!rowCards.length) {
		mutateLayout()
		return
	}

	const firstRects = new Map()
	rowCards.forEach((card) => {
		firstRects.set(card, card.getBoundingClientRect())
	})

	mutateLayout()

	if (homeReducedMotion) {
		return
	}

	window.requestAnimationFrame(() => {
		rowCards.forEach((card) => {
			card.getAnimations().forEach((animation) => animation.cancel())

			const firstRect = firstRects.get(card)
			const lastRect = card.getBoundingClientRect()

			if (!firstRect || !lastRect) {
				return
			}

			const deltaX = firstRect.left - lastRect.left
			const deltaY = firstRect.top - lastRect.top
			const scaleX = lastRect.width > 0 ? firstRect.width / lastRect.width : 1
			const scaleY = lastRect.height > 0 ? firstRect.height / lastRect.height : 1

			if (
				Math.abs(deltaX) < 0.5 &&
				Math.abs(deltaY) < 0.5 &&
				Math.abs(scaleX - 1) < 0.01 &&
				Math.abs(scaleY - 1) < 0.01
			) {
				return
			}

			card.animate(
				[
					{
						transformOrigin: 'top left',
						transform: `translate(${deltaX}px, ${deltaY}px) scale(${scaleX}, ${scaleY})`,
					},
					{
						transformOrigin: 'top left',
						transform: 'translate(0, 0) scale(1, 1)',
					},
				],
				{
					duration: 800,
					easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
					fill: 'both',
				}
			)
		})
	})
}

const setExpandedHomeCard = (row, targetCard) => {
	const rowCards = Array.from(row.querySelectorAll(HOME_FABRIC_CARD_SELECTOR))
	const activeExpandedCard = rowCards.find((card) => card.classList.contains('is-expanded')) || null

	if (activeExpandedCard && activeExpandedCard !== targetCard) {
		return
	}

	const nextExpandedCard = targetCard && targetCard.classList.contains('is-expanded')
		? null
		: targetCard

	animateHomeRowLayout(row, () => {
		row.classList.toggle('has-expanded', Boolean(nextExpandedCard))

		rowCards.forEach((card) => {
			const isExpanded = card === nextExpandedCard
			card.classList.toggle('is-expanded', isExpanded)
			card.classList.toggle('is-collapsed', Boolean(nextExpandedCard) && !isExpanded)
			setHomeCardToggleState(card, isExpanded)

			if (nextExpandedCard) {
				applyHomeCardLayout(card, isExpanded ? 'expanded' : 'default')
			} else {
				applyHomeCardLayout(card, 'default')
			}
		})
	})
}

const initHomeFabrics = () => {
	const listing = document.querySelector(HOME_FABRICS_SELECTOR)
	if (!listing) {
		return
	}

	if (listing.dataset.homeFabricsBound === 'true') {
		return
	}

	const cards = Array.from(listing.querySelectorAll(HOME_FABRIC_CARD_SELECTOR))
	if (!cards.length) {
		return
	}

	initHomeFabricsParallax(listing)

	cards.forEach((card, index) => {
		getHomeCardState(card)
		applyHomeCardLayout(card, 'default')
		resetHomeFabricImage(card)
		resetHomeActiveKitchenLink(card)
		setHomeCardToggleState(card, false)
		setupHomeCardAutoplay(card, index)
	})

	const onKitchenHover = (event) => {
		const link = event.target.closest(HOME_FABRIC_KITCHEN_LINK_SELECTOR)
		if (!link || !listing.contains(link)) {
			return
		}

		const card = link.closest(HOME_FABRIC_CARD_SELECTOR)
		if (!card) {
			return
		}

		const state = getHomeCardState(card)
		state.isPointerInside = true
		const linkIndex = state.links.indexOf(link)
		if (linkIndex >= 0) {
			setHomeCardByIndex(card, linkIndex)
		}
	}

	const onKitchenLeave = (event) => {
		const link = event.target.closest(HOME_FABRIC_KITCHEN_LINK_SELECTOR)
		if (!link || !listing.contains(link)) {
			return
		}

		const card = link.closest(HOME_FABRIC_CARD_SELECTOR)
		if (!card) {
			return
		}

		if (card.contains(event.relatedTarget)) {
			return
		}

		if (card.classList.contains('is-expanded')) {
			return
		}

		setHomeCardByIndex(card, 0)
	}

	const onCardMouseOver = (event) => {
		const card = event.target.closest(HOME_FABRIC_CARD_SELECTOR)
		if (!card || !listing.contains(card)) {
			return
		}

		if (card.contains(event.relatedTarget)) {
			return
		}

		const state = getHomeCardState(card)
		state.isPointerInside = true
	}

	const onCardMouseOut = (event) => {
		const card = event.target.closest(HOME_FABRIC_CARD_SELECTOR)
		if (!card || !listing.contains(card)) {
			return
		}

		if (card.contains(event.relatedTarget)) {
			return
		}

		const state = getHomeCardState(card)
		state.isPointerInside = false

		if (!card.classList.contains('is-expanded')) {
			setHomeCardByIndex(card, 0)
		}
	}

	const onCardFocusIn = (event) => {
		const card = event.target.closest(HOME_FABRIC_CARD_SELECTOR)
		if (!card || !listing.contains(card)) {
			return
		}

		const state = getHomeCardState(card)
		state.isFocusInside = true
	}

	const onCardFocusOut = (event) => {
		const card = event.target.closest(HOME_FABRIC_CARD_SELECTOR)
		if (!card || !listing.contains(card)) {
			return
		}

		if (card.contains(event.relatedTarget)) {
			return
		}

		const state = getHomeCardState(card)
		state.isFocusInside = false

		if (!card.classList.contains('is-expanded')) {
			setHomeCardByIndex(card, 0)
		}
	}

	const onCardImageClick = (event) => {
		if (event.target.closest('a')) {
			return
		}

		const media = event.target.closest(HOME_FABRIC_MEDIA_SELECTOR)
		if (!media || !listing.contains(media)) {
			return
		}

		const card = media.closest(HOME_FABRIC_CARD_SELECTOR)
		if (!card || !listing.contains(card)) {
			return
		}

		const row = card.closest(HOME_FABRIC_ROW_SELECTOR)
		if (!row) {
			return
		}

		setExpandedHomeCard(row, card)
	}

	const onToggleClick = (event) => {
		const toggle = event.target.closest(HOME_FABRIC_TOGGLE_SELECTOR)
		if (!toggle || !listing.contains(toggle)) {
			return
		}

		const card = toggle.closest(HOME_FABRIC_CARD_SELECTOR)
		if (!card) {
			return
		}

		const row = card.closest(HOME_FABRIC_ROW_SELECTOR)
		if (!row) {
			return
		}

		setExpandedHomeCard(row, card)
	}

	listing.addEventListener('mouseover', onKitchenHover)
	listing.addEventListener('focusin', onKitchenHover)
	listing.addEventListener('mouseout', onKitchenLeave)
	listing.addEventListener('focusout', onKitchenLeave)
	listing.addEventListener('mouseover', onCardMouseOver)
	listing.addEventListener('mouseout', onCardMouseOut)
	listing.addEventListener('focusin', onCardFocusIn)
	listing.addEventListener('focusout', onCardFocusOut)
	listing.addEventListener('click', onCardImageClick)
	listing.addEventListener('click', onToggleClick)

	listing.dataset.homeFabricsBound = 'true'
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initHomeFabrics)
} else {
	initHomeFabrics()
}

document.addEventListener('swup:content:replace', initHomeFabrics)
document.addEventListener('swup:page:view', initHomeFabrics)