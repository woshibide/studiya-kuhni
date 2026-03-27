const heroTyped = document.querySelector('#home-hero-typed')

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

    const TYPE_DELAY_MS = 143
    const ERASE_DELAY_MS = 82
    const FULL_WORD_PAUSE_MS = 1500
    const EMPTY_WORD_PAUSE_MS = 400
    const TYPE_VARIANCE_MS = 14
    const ERASE_VARIANCE_MS = 8

	const nextDelay = (base, variance) => {
		const swing = Math.round((Math.random() * 2 - 1) * variance)
		return Math.max(16, base + swing)
	}

	const wait = (ms) => new Promise((resolve) => {
		window.setTimeout(resolve, ms)
	})

	if (reducedMotion || words.length === 1) {
		heroTyped.textContent = words[0]
		heroTyped.classList.add('is-static')
	} else {
		const typeWord = async (word) => {
			for (let index = 1; index <= word.length; index += 1) {
				heroTyped.textContent = word.slice(0, index)
				await wait(nextDelay(TYPE_DELAY_MS, TYPE_VARIANCE_MS))
			}
		}

		const eraseWord = async (word) => {
			for (let index = word.length - 1; index >= 0; index -= 1) {
				heroTyped.textContent = word.slice(0, index)
				await wait(nextDelay(ERASE_DELAY_MS, ERASE_VARIANCE_MS))
			}
		}

		const cycle = async () => {
			let wordIndex = 0
			heroTyped.textContent = ''

			while (true) {
				const activeWord = words[wordIndex]
				heroTyped.textContent = ''
				await typeWord(activeWord)
				await wait(FULL_WORD_PAUSE_MS)
				await eraseWord(activeWord)
				await wait(EMPTY_WORD_PAUSE_MS)
				wordIndex = (wordIndex + 1) % words.length
			}
		}

		cycle()
	}
}

const HOME_FABRICS_SELECTOR = '#fabrics-intro .fabrics-listing'
const HOME_FABRIC_CARD_SELECTOR = '[data-home-fabric-card]'
const HOME_FABRIC_ROW_SELECTOR = '[data-home-fabric-row]'
const HOME_FABRIC_MEDIA_SELECTOR = '.home-fabric-media'
const HOME_FABRIC_KITCHEN_LINK_SELECTOR = 'a[data-fabric-image]'
const HOME_FABRIC_TOGGLE_SELECTOR = '[data-home-fabric-toggle]'
const HOME_FABRIC_EMBLA_VIEWPORT_SELECTOR = '[data-home-embla-viewport]'
const HOME_FABRIC_EMBLA_SLIDE_SELECTOR = '.home-fabric-media__slide'
const HOME_EXPANDED_COL_START = '1'
const HOME_EXPANDED_COL_SPAN = '12'
const HOME_AUTOPLAY_INTERVAL_MS = 3000
const HOME_EMBLA_DEBUG = window.location.search.includes('emblaDebug=1') || window.localStorage.getItem('emblaDebug') === '1'
const homeReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
const homeCardState = new WeakMap()
let homeFabricsEmblaCleanup = null

const getHomeCardState = (card) => {
	if (homeCardState.has(card)) {
		return homeCardState.get(card)
	}

	const links = Array.from(card.querySelectorAll(HOME_FABRIC_KITCHEN_LINK_SELECTOR))
	const state = {
		links,
		activeIndex: 0,
		emblaApi: null,
		isPointerInside: false,
		isFocusInside: false,
		autoplayTimerId: null,
	}

	homeCardState.set(card, state)
	return state
}

const destroyHomeCardEmbla = (card) => {
	const state = getHomeCardState(card)
	if (state.emblaApi) {
		state.emblaApi.destroy()
		state.emblaApi = null
	}
}

const clearHomeCardAutoplay = (card) => {
	const state = getHomeCardState(card)
	if (state.autoplayTimerId) {
		window.clearTimeout(state.autoplayTimerId)
		state.autoplayTimerId = null
	}
}

const getHomeSlideCount = (state) => {
	if (!state.emblaApi) {
		return 0
	}

	return state.emblaApi.scrollSnapList().length
}

const setHomeActiveKitchenLink = (card, activeIndex) => {
	const state = getHomeCardState(card)
	if (!state.links.length) {
		state.activeIndex = 0
		return
	}

	const normalizedIndex = state.links.length === 1
		? 0
		: ((activeIndex % state.links.length) + state.links.length) % state.links.length

	state.links.forEach((link, index) => {
		const isActive = index === normalizedIndex
		link.classList.toggle('is-active', isActive)
		if (isActive) {
			link.setAttribute('aria-current', 'true')
		} else {
			link.removeAttribute('aria-current')
		}
	})
	state.activeIndex = normalizedIndex
}

const resetHomeActiveKitchenLink = (card) => {
	const state = getHomeCardState(card)
	if (!state.links.length) {
		return
	}

	setHomeActiveKitchenLink(card, 0)
}

const setHomeCardByIndex = (card, index, options = {}) => {
	const { jump = false } = options
	const state = getHomeCardState(card)
	if (!state.links.length) {
		return
	}

	const normalized = ((index % state.links.length) + state.links.length) % state.links.length
	setHomeActiveKitchenLink(card, normalized)

	if (state.emblaApi) {
		state.emblaApi.scrollTo(normalized, jump)
	}
}

const getHomeLinkIndex = (state, link) => {
	const datasetIndex = Number(link.dataset.homeSlideIndex)
	if (Number.isInteger(datasetIndex) && datasetIndex >= 0) {
		return datasetIndex
	}

	return state.links.indexOf(link)
}

const initHomeCardEmbla = (card) => {
	const state = getHomeCardState(card)
	const media = card.querySelector(HOME_FABRIC_MEDIA_SELECTOR)
	if (!media) {
		return
	}

	const viewport = media.querySelector(HOME_FABRIC_EMBLA_VIEWPORT_SELECTOR)
	if (!viewport) {
		return
	}

	destroyHomeCardEmbla(card)

	const slideCount = media.querySelectorAll(HOME_FABRIC_EMBLA_SLIDE_SELECTOR).length
	if (slideCount <= 1 || typeof EmblaCarousel !== 'function') {
		setHomeCardByIndex(card, 0, { jump: true })
		return
	}

	state.emblaApi = EmblaCarousel(viewport, {
		axis: 'y',
		loop: true,
		align: 'start',
		containScroll: false,
		dragFree: false,
		duration: homeReducedMotion ? 20 : 32,
	})

	const syncSelection = () => {
		if (!state.emblaApi) {
			return
		}

		setHomeActiveKitchenLink(card, state.emblaApi.selectedScrollSnap())
	}

	state.emblaApi.on('select', syncSelection)
	state.emblaApi.on('reInit', syncSelection)
	syncSelection()
}

const shouldHomeCardAutoplay = (card) => {
	if (homeReducedMotion) {
		return false
	}

	const state = getHomeCardState(card)
	if (!state.emblaApi || getHomeSlideCount(state) <= 1) {
		return false
	}

	if (!state.isPointerInside && !state.isFocusInside) {
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
	state.emblaApi.scrollNext()
}

const queueHomeCardAutoplay = (card, delay = HOME_AUTOPLAY_INTERVAL_MS) => {
	clearHomeCardAutoplay(card)

	if (!shouldHomeCardAutoplay(card)) {
		return
	}

	const state = getHomeCardState(card)
	state.autoplayTimerId = window.setTimeout(() => {
		state.autoplayTimerId = null

		if (!shouldHomeCardAutoplay(card)) {
			return
		}

		advanceHomeCardAutoplay(card)
		queueHomeCardAutoplay(card, HOME_AUTOPLAY_INTERVAL_MS)
	}, delay)
}

const setupHomeCardAutoplay = (card) => {
	clearHomeCardAutoplay(card)
	const state = getHomeCardState(card)

	if (homeReducedMotion || !state.emblaApi || getHomeSlideCount(state) <= 1) {
		return
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
	if (homeFabricsEmblaCleanup) {
		homeFabricsEmblaCleanup()
		homeFabricsEmblaCleanup = null
	}

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

	let homeEmblaInitializedCount = 0

	const cardCleanupHandlers = []

	cards.forEach((card) => {
		getHomeCardState(card)
		applyHomeCardLayout(card, 'default')
		resetHomeActiveKitchenLink(card)
		setHomeCardToggleState(card, false)
		initHomeCardEmbla(card)
		setupHomeCardAutoplay(card)
		homeEmblaInitializedCount += 1

		cardCleanupHandlers.push(() => {
			clearHomeCardAutoplay(card)
			destroyHomeCardEmbla(card)
		})
	})

	if (HOME_EMBLA_DEBUG) {
		console.info('[home embla debug] initialized immediately', {
			totalCards: cards.length,
			initializedCards: homeEmblaInitializedCount,
			hasIntersectionObserverInit: false,
		})
	}

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
		const linkIndex = getHomeLinkIndex(state, link)
		state.isPointerInside = true
		queueHomeCardAutoplay(card)
		if (linkIndex >= 0) {
			setHomeCardByIndex(card, linkIndex)
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
		queueHomeCardAutoplay(card)
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
		clearHomeCardAutoplay(card)
		queueHomeCardAutoplay(card)
	}

	const onCardFocusIn = (event) => {
		const card = event.target.closest(HOME_FABRIC_CARD_SELECTOR)
		if (!card || !listing.contains(card)) {
			return
		}

		const state = getHomeCardState(card)
		state.isFocusInside = true
		queueHomeCardAutoplay(card)
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
		clearHomeCardAutoplay(card)
		queueHomeCardAutoplay(card)
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
	listing.addEventListener('mouseover', onCardMouseOver)
	listing.addEventListener('mouseout', onCardMouseOut)
	listing.addEventListener('focusin', onCardFocusIn)
	listing.addEventListener('focusout', onCardFocusOut)
	listing.addEventListener('click', onCardImageClick)
	listing.addEventListener('click', onToggleClick)

	homeFabricsEmblaCleanup = () => {
		cardCleanupHandlers.forEach((cleanup) => cleanup())
	}

	listing.dataset.homeFabricsBound = 'true'
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initHomeFabrics)
} else {
	initHomeFabrics()
}

document.addEventListener('swup:content:replace', initHomeFabrics)
document.addEventListener('swup:page:view', initHomeFabrics)