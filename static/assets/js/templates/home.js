const heroTyped = document.querySelector('#home-hero-typed')

if (heroTyped) {
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
	const wordsSource = heroTyped.dataset.words ?? '[]'
	let words = []

	try {
		const parsed = JSON.parse(wordsSource)
		words = Array.isArray(parsed)
			? parsed.map((word) => String(word)).filter(Boolean)
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
const HOME_LAYOUT_FLIP_DURATION_MS = 300
const HOME_LAYOUT_FLIP_EASING = 'ease-in-out'
const HOME_LAYOUT_FLIP_ANIMATION_ID = 'home-layout-flip'
const HOME_VIEWPORT_BOTTOM_GAP_CSS_VAR = '--space-md'
const HOME_VIEWPORT_BOTTOM_GAP_FALLBACK_PX = 24
const HOME_VIEWPORT_ALIGNMENT_THRESHOLD_PX = 1
const HOME_EMBLA_DEBUG = window.location.search.includes('emblaDebug=1') || window.localStorage.getItem('emblaDebug') === '1'
const homeReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
const homeCardState = new WeakMap()
let homeFabricsEmblaCleanup = null
let homeViewportScrollRafId = null

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
		watchDrag: false,
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

const getHomeCssLengthInPx = (cssLength, fallbackPx = 0) => {
	if (!cssLength || !document.body) {
		return fallbackPx
	}

	const probe = document.createElement('div')
	probe.style.position = 'absolute'
	probe.style.visibility = 'hidden'
	probe.style.pointerEvents = 'none'
	probe.style.width = '0'
	probe.style.height = cssLength
	document.body.appendChild(probe)

	const measured = probe.getBoundingClientRect().height
	probe.remove()

	if (!Number.isFinite(measured) || measured <= 0) {
		return fallbackPx
	}

	return measured
}

const getHomeViewportBottomGapPx = () => (
	getHomeCssLengthInPx(`var(${HOME_VIEWPORT_BOTTOM_GAP_CSS_VAR})`, HOME_VIEWPORT_BOTTOM_GAP_FALLBACK_PX)
)

const clampHomeScrollTop = (scrollTop) => {
	const maxScrollTop = Math.max(0, document.documentElement.scrollHeight - window.innerHeight)
	return Math.min(maxScrollTop, Math.max(0, scrollTop))
}

const getHomeViewportTargetScrollTopFromRect = (cardRect) => {
	const bottomGap = getHomeViewportBottomGapPx()
	const desiredBottom = window.innerHeight - bottomGap
	const delta = cardRect.bottom - desiredBottom

	if (Math.abs(delta) < HOME_VIEWPORT_ALIGNMENT_THRESHOLD_PX) {
		return window.scrollY
	}

	return clampHomeScrollTop(window.scrollY + delta)
}

const stopHomeViewportScrollAnimation = () => {
	if (homeViewportScrollRafId === null) {
		return
	}

	window.cancelAnimationFrame(homeViewportScrollRafId)
	homeViewportScrollRafId = null
}

const easeInOutQuad = (progress) => (
	progress < 0.5
		? 2 * progress * progress
		: 1 - (Math.pow(-2 * progress + 2, 2) / 2)
)

const smoothScrollHomeViewportTo = (targetScrollTop) => {
	const clampedTarget = clampHomeScrollTop(targetScrollTop)
	const startScrollTop = window.scrollY
	const delta = clampedTarget - startScrollTop

	if (Math.abs(delta) < HOME_VIEWPORT_ALIGNMENT_THRESHOLD_PX) {
		return
	}

	stopHomeViewportScrollAnimation()

	if (homeReducedMotion) {
		window.scrollTo(0, clampedTarget)
		return
	}

	const startedAt = performance.now()
	const duration = HOME_LAYOUT_FLIP_DURATION_MS
	
	// TODO: orchestrated card expansion, first the width, then the height

	const tick = (now) => {
		const elapsed = now - startedAt
		const progress = Math.min(1, elapsed / duration)
		const eased = easeInOutQuad(progress)
		window.scrollTo(0, startScrollTop + (delta * eased))

		if (progress < 1) {
			homeViewportScrollRafId = window.requestAnimationFrame(tick)
			return
		}

		homeViewportScrollRafId = null
	}

	homeViewportScrollRafId = window.requestAnimationFrame(tick)
}

const applyHomeExpandedRowState = (row, rowCards, nextExpandedCard, options = {}) => {

	// TODO: transform change added 

	const { updateToggle = true } = options

	row.classList.toggle('has-expanded', Boolean(nextExpandedCard))

	rowCards.forEach((card) => {
		const isExpanded = card === nextExpandedCard
		card.classList.toggle('is-expanded', isExpanded)
		card.classList.toggle('is-collapsed', Boolean(nextExpandedCard) && !isExpanded)

		if (updateToggle) {
			setHomeCardToggleState(card, isExpanded)
		}

		if (nextExpandedCard) {
			applyHomeCardLayout(card, isExpanded ? 'expanded' : 'default')
		} else {
			applyHomeCardLayout(card, 'default')
		}
	})
}

const captureHomeRowState = (row, rowCards) => ({
	rowHasExpanded: row.classList.contains('has-expanded'),
	cards: rowCards.map((card) => ({
		card,
		isExpanded: card.classList.contains('is-expanded'),
		isCollapsed: card.classList.contains('is-collapsed'),
		colStart: card.style.getPropertyValue('--col-start'),
		colSpan: card.style.getPropertyValue('--col-span'),
		marginTop: card.style.getPropertyValue('margin-top'),
	})),
})

const restoreHomeRowState = (row, snapshot) => {
	row.classList.toggle('has-expanded', snapshot.rowHasExpanded)

	snapshot.cards.forEach((item) => {
		const { card, isExpanded, isCollapsed, colStart, colSpan, marginTop } = item
		card.classList.toggle('is-expanded', isExpanded)
		card.classList.toggle('is-collapsed', isCollapsed)
		setStyleProperty(card, '--col-start', colStart)
		setStyleProperty(card, '--col-span', colSpan)
		setStyleProperty(card, 'margin-top', marginTop)
		setHomeCardToggleState(card, isExpanded)
	})
}

const getExpandedHomeCardTargetScrollTop = (row, rowCards, expandedCard) => {
	if (!expandedCard || !expandedCard.isConnected) {
		return null
	}

	const snapshot = captureHomeRowState(row, rowCards)
	applyHomeExpandedRowState(row, rowCards, expandedCard, { updateToggle: false })

	const targetScrollTop = getHomeViewportTargetScrollTopFromRect(expandedCard.getBoundingClientRect())

	restoreHomeRowState(row, snapshot)
	return targetScrollTop
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
			card.getAnimations().forEach((animation) => {
				if (animation.id === HOME_LAYOUT_FLIP_ANIMATION_ID) {
					animation.cancel()
				}
			})

			const firstRect = firstRects.get(card)
			const lastRect = card.getBoundingClientRect()

			if (!firstRect || !lastRect) {
				return
			}

			const deltaX = firstRect.left - lastRect.left
			const deltaY = firstRect.top - lastRect.top
			const widthDelta = Math.abs(firstRect.width - lastRect.width)
			const heightDelta = Math.abs(firstRect.height - lastRect.height)

			if (
				Math.abs(deltaX) < 0.5 &&
				Math.abs(deltaY) < 0.5 &&
				widthDelta < 0.5 &&
				heightDelta < 0.5
			) {
				return
			}

			const cardAnimation = card.animate(
				[
					{
						transformOrigin: 'top left',
						transform: `translate(${deltaX}px, ${deltaY}px)`,
						width: `${firstRect.width}px`,
						height: `${firstRect.height}px`,
					},
					{
						transformOrigin: 'top left',
						transform: 'translate(0, 0)',
						width: `${lastRect.width}px`,
						height: `${lastRect.height}px`,
					},
				],
				{
					duration: HOME_LAYOUT_FLIP_DURATION_MS,
					easing: HOME_LAYOUT_FLIP_EASING,
					fill: 'none',
				}
			)

			cardAnimation.id = HOME_LAYOUT_FLIP_ANIMATION_ID
		})
	})
}

const setExpandedHomeCard = (row, targetCard) => {
	const rowCards = Array.from(row.querySelectorAll(HOME_FABRIC_CARD_SELECTOR))

	const nextExpandedCard = targetCard && targetCard.classList.contains('is-expanded')
		? null
		: targetCard

	const nextExpandedCardTargetScrollTop = nextExpandedCard
		? getExpandedHomeCardTargetScrollTop(row, rowCards, nextExpandedCard)
		: null

	animateHomeRowLayout(row, () => {
		applyHomeExpandedRowState(row, rowCards, nextExpandedCard)
	})

	if (nextExpandedCard && nextExpandedCardTargetScrollTop !== null) {
		smoothScrollHomeViewportTo(nextExpandedCardTargetScrollTop)
	} else {
		stopHomeViewportScrollAnimation()
	}
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