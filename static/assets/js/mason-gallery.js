const HOME_FABRICS_SELECTOR = '#fabrics-intro .fabrics-listing, #details .kuhnya-layout-grid'
const HOME_FABRIC_ROW_SELECTOR = '[data-home-fabric-row], .kuhnya-layout-grid'
const HOME_FABRIC_CARD_SELECTOR = '[data-home-fabric-card], [data-kuhnya-layout-card]'
const HOME_FABRIC_MEDIA_SELECTOR = '.home-fabric-media, .kuhnya-layout-media'
const HOME_FABRIC_KITCHEN_LINK_SELECTOR = 'a[data-fabric-image]'
const HOME_FABRIC_TOGGLE_SELECTOR = '[data-home-fabric-toggle], [data-kuhnya-layout-toggle]'
const HOME_FABRIC_EMBLA_VIEWPORT_SELECTOR = '[data-home-embla-viewport]'
const HOME_FABRIC_EMBLA_SLIDE_SELECTOR = '.home-fabric-media__slide'

const HOME_EXPANDED_COL_START = '1'
const HOME_EXPANDED_COL_SPAN = '12'
const HOME_LAYOUT_FLIP_DURATION_MS = 1200
const HOME_LAYOUT_FLIP_CLOSE_DURATION_MS = 2800
const HOME_LAYOUT_FLIP_HORIZONTAL_PHASE = 0.6
const HOME_LAYOUT_FLIP_EASE_OUT = 'cubic-bezier(0.87, 0, 0.13, 1)'
const HOME_LAYOUT_FLIP_EASE_IN = 'cubic-bezier(0.16, 1, 0.3, 1)'
const HOME_ANIMATION_ID_PREFIX = 'home-layout'
const HOME_MOBILE_QUERY = '(max-width: 48rem)'
const HOME_ANIMATION_DEBUG = true

const homeReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
const homeMobileQuery = window.matchMedia(HOME_MOBILE_QUERY)

// track state using weakmaps to avoid memory leaks
const homeRowState = new WeakMap()
const homeCardState = new WeakMap()

const isHomeMobile = () => homeMobileQuery.matches

const logHomeDebug = (eventName, payload = {}) => {
	if (!HOME_ANIMATION_DEBUG) {
		return
	}
	console.log('[home animation]', eventName, payload)
}

const getRowState = (row) => {
	if (homeRowState.has(row)) {
		return homeRowState.get(row)
	}
	const state = { isAnimating: false }
	homeRowState.set(row, state)
	return state
}

const getCardState = (card) => {
	if (homeCardState.has(card)) {
		return homeCardState.get(card)
	}
	const state = {
		links: Array.from(card.querySelectorAll(HOME_FABRIC_KITCHEN_LINK_SELECTOR)),
		emblaApi: null,
		activeIndex: 0,
	}
	homeCardState.set(card, state)
	return state
}

const destroyCardEmbla = (card) => {
	const state = getCardState(card)
	if (state.emblaApi) {
		state.emblaApi.destroy()
		state.emblaApi = null
	}
}

const setActiveKitchenLink = (card, activeIndex) => {
	const state = getCardState(card)
	if (!state.links.length) {
		state.activeIndex = 0
		return
	}
	const normalized = state.links.length === 1
		? 0
		: ((activeIndex % state.links.length) + state.links.length) % state.links.length
	state.links.forEach((link, index) => {
		const isActive = index === normalized
		link.classList.toggle('is-active', isActive)
		if (isActive) {
			link.setAttribute('aria-current', 'true')
		} else {
			link.removeAttribute('aria-current')
		}
	})
	state.activeIndex = normalized
}

const setCardByIndex = (card, index, options = {}) => {
	const { jump = false } = options
	const state = getCardState(card)
	if (!state.links.length) {
		return
	}
	const normalized = ((index % state.links.length) + state.links.length) % state.links.length
	setActiveKitchenLink(card, normalized)
	if (state.emblaApi) {
		state.emblaApi.scrollTo(normalized, jump)
	}
}

const getLinkIndex = (state, link) => {
	const datasetIndex = Number(link.dataset.homeSlideIndex)
	if (Number.isInteger(datasetIndex) && datasetIndex >= 0) {
		return datasetIndex
	}
	return state.links.indexOf(link)
}

// initialize embla carousel inside card media
const initCardEmbla = (card) => {
	const state = getCardState(card)
	const media = card.querySelector(HOME_FABRIC_MEDIA_SELECTOR)
	if (!media) {
		return
	}
	const viewport = media.querySelector(HOME_FABRIC_EMBLA_VIEWPORT_SELECTOR)
	if (!viewport) {
		return
	}
	destroyCardEmbla(card)
	const slideCount = media.querySelectorAll(HOME_FABRIC_EMBLA_SLIDE_SELECTOR).length
	if (slideCount <= 1 || typeof EmblaCarousel !== 'function') {
		setCardByIndex(card, 0, { jump: true })
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
		setActiveKitchenLink(card, state.emblaApi.selectedScrollSnap())
	}
	state.emblaApi.on('select', syncSelection)
	state.emblaApi.on('reInit', syncSelection)
	syncSelection()
}

const setStyle = (el, prop, value) => {
	if (value && value !== '') {
		el.style.setProperty(prop, value)
	} else {
		el.style.removeProperty(prop)
	}
}

const rememberOriginalLayout = (card) => {
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

const setToggleState = (card, expanded) => {
	const toggle = card.querySelector(HOME_FABRIC_TOGGLE_SELECTOR)
	if (!toggle) {
		return
	}
	toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false')
	toggle.setAttribute('aria-label', expanded ? 'Minimize fabric card' : 'Expand fabric card')
	toggle.textContent = expanded ? '-' : '+'
}

const setToggleInteractivity = (card, disabled) => {
	const toggle = card.querySelector(HOME_FABRIC_TOGGLE_SELECTOR)
	if (!toggle) {
		return
	}
	if (disabled) {
		toggle.setAttribute('aria-disabled', 'true')
		toggle.setAttribute('tabindex', '-1')
	} else {
		toggle.removeAttribute('aria-disabled')
		toggle.removeAttribute('tabindex')
	}
}


// apply grid styles for expanded and collapsed states

const applyCardLayout = (card, expanded) => {
	rememberOriginalLayout(card)
	if (expanded) {
		setStyle(card, '--col-start', HOME_EXPANDED_COL_START)
		setStyle(card, '--col-span', HOME_EXPANDED_COL_SPAN)
		setStyle(card, 'margin-top', '0')
		return
	}
	setStyle(card, '--col-start', card.dataset.originalColStart)
	setStyle(card, '--col-span', card.dataset.originalColSpan)
	setStyle(card, 'margin-top', card.dataset.originalMarginTop)
}

const lockCardDimensions = (card, rect) => {
	card.style.width = `${rect.width}px`
	card.style.minHeight = `${rect.height}px`
}

const clearCardDimensions = (card) => {
	card.style.removeProperty('width')
	card.style.removeProperty('min-height')
}

const applyRowState = (row, cards, expandedCard) => {
	row.classList.toggle('has-expanded', Boolean(expandedCard))
	cards.forEach((card) => {
		const isExpanded = card === expandedCard
		card.classList.toggle('is-expanded', isExpanded)
		card.classList.toggle('is-collapsed', Boolean(expandedCard) && !isExpanded)
		setToggleState(card, isExpanded)
		applyCardLayout(card, Boolean(expandedCard && isExpanded))
	})
}



// orchestrate flip animation for smooth transitions
const animateRow = (cards, direction, mutate) => {
	if (homeReducedMotion) {
		mutate()
		return Promise.resolve()
	}

	const firstRects = new Map()
	cards.forEach((card) => {
		card.getAnimations().forEach((animation) => {
			if (typeof animation.id === 'string' && animation.id.startsWith(HOME_ANIMATION_ID_PREFIX)) {
				animation.cancel()
			}
		})
		firstRects.set(card, card.getBoundingClientRect())
	})

	mutate()

	const isExpand = direction === 'expand'
	const duration = isExpand ? HOME_LAYOUT_FLIP_DURATION_MS : HOME_LAYOUT_FLIP_CLOSE_DURATION_MS
	const phase = HOME_LAYOUT_FLIP_HORIZONTAL_PHASE
	const firstEase = isExpand ? HOME_LAYOUT_FLIP_EASE_IN : HOME_LAYOUT_FLIP_EASE_OUT
	const secondEase = isExpand ? HOME_LAYOUT_FLIP_EASE_OUT : HOME_LAYOUT_FLIP_EASE_IN

	return Promise.all(cards.map((card, index) => new Promise((resolveCard) => {
		const first = firstRects.get(card)
		const last = card.getBoundingClientRect()
		if (!first || !last) {
			resolveCard()
			return
		}
        

		// calculate inverse deltas for flip technique
		const dx = first.left - last.left
		const dy = first.top - last.top
		const dw = Math.abs(first.width - last.width)
		const dh = Math.abs(first.height - last.height)
		if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5 && dw < 0.5 && dh < 0.5) {
			clearCardDimensions(card)
			// logHomeDebug('card:skip:no-delta', { cardIndex: index, dx, dy, dw, dh })
			resolveCard()
			return
		}

		lockCardDimensions(card, first)
		const keyframes = isExpand
			? [
				{ offset: 0, 
                    transformOrigin: 'top left', 
                    transform: `translate(${dx}px, ${dy}px)`, 
                    width: `${first.width}px`, 
                    minHeight: `${first.height}px`, 
                    easing: firstEase },
                    
				{ offset: phase, 
                    transformOrigin: 'top left', 
                    transform: `translate(0, ${dy}px)`, 
                    width: `${last.width}px`, 
                    minHeight: `${first.height}px`, 
                    easing: secondEase },

				{ offset: 1, 
                    transformOrigin: 'top left', 
                    transform: 'translate(0, 0)', 
                    width: `${last.width}px`, 
                    minHeight: `${last.height}px` },
			]
			: [
				{ offset: 0, 
                    transformOrigin: 'top left', 
                    transform: `translate(${dx}px, ${dy}px)`, 
                    idth: `${first.width}px`, 
                    minHeight: `${first.height}px`, 
                    easing: firstEase },

				{ offset: phase, 
                    transformOrigin: 'top left', 
                    transform: `translate(${dx}px, 0)`, 
                    width: `${first.width}px`, 
                    minHeight: `${last.height}px`, 
                    easing: secondEase },

				{ offset: 1, 
                    transformOrigin: 'top left', 
                    transform: 'translate(0, 0)', 
                    width: `${last.width}px`, 
                    minHeight: `${last.height}px` },
			]
// execute web animations api with calculated keyframes
		
		// logHomeDebug('card:animate:timeline', { cardIndex: index, direction, duration, dx, dy })
		const animation = card.animate(keyframes, { duration })
		animation.id = `${HOME_ANIMATION_ID_PREFIX}-${direction}`
		const finish = (e) => {
			animation.onfinish = null
			animation.oncancel = null
			try { animation.cancel() } catch(err) {}
			clearCardDimensions(card)
			resolveCard()
		}
		animation.onfinish = finish
		animation.oncancel = finish
	})))
}

const setRowExpandedCard = (row, card) => {
	if (isHomeMobile()) {
		return
	}
	const rowState = getRowState(row)
	if (rowState.isAnimating) {
		// logHomeDebug('row:toggle:ignored-animating', {})
		return
	}

	const cards = Array.from(row.querySelectorAll(HOME_FABRIC_CARD_SELECTOR))
	const nextExpanded = card.classList.contains('is-expanded') ? null : card
	const direction = nextExpanded ? 'expand' : 'collapse'
	// logHomeDebug('row:toggle', { direction, rowCards: cards.length })

	rowState.isAnimating = true
	row.classList.add('is-animating')
	animateRow(cards, direction, () => applyRowState(row, cards, nextExpanded)).finally(() => {
		rowState.isAnimating = false
		row.classList.remove('is-animating')
		// logHomeDebug('row:animate:done', { direction })
	})

	// if (nextExpanded) {
	// 	window.requestAnimationFrame(() => {
	// 		nextExpanded.scrollIntoView({ behavior: homeReducedMotion ? 'auto' : 'smooth', block: 'end', inline: 'nearest' })
	// 	})
	// }
}

const setRowsExpandedByDefault = (listing) => {
	Array.from(listing.querySelectorAll(HOME_FABRIC_ROW_SELECTOR)).forEach((row) => {
		const cards = Array.from(row.querySelectorAll(HOME_FABRIC_CARD_SELECTOR))
		if (!cards.length) {
			return
		}
		row.classList.add('has-expanded')
		row.classList.remove('is-animating')
		cards.forEach((card) => {
			card.classList.add('is-expanded')
			card.classList.remove('is-collapsed')
			setToggleState(card, true)
			setToggleInteractivity(card, true)
			applyCardLayout(card, true)
		})
	})
}

const syncMobileMode = (listing, cards) => {
	const mobile = isHomeMobile()
	cards.forEach((card) => setToggleInteractivity(card, mobile))
	if (mobile) {
		setRowsExpandedByDefault(listing)
	}
}

// bind listeners and initialize masonry gallery logic
const initHomeFabrics = () => {
	const listings = document.querySelectorAll(HOME_FABRICS_SELECTOR)
	listings.forEach(listing => {
		if (listing.dataset.homeFabricsBound === 'true') {
			return
		}
		const cards = Array.from(listing.querySelectorAll(HOME_FABRIC_CARD_SELECTOR))
		if (!cards.length) {
			return
		}

		cards.forEach((card) => {
			applyCardLayout(card, false)
			setToggleState(card, false)
			setToggleInteractivity(card, false)
			initCardEmbla(card)
		})

		syncMobileMode(listing, cards)
		homeMobileQuery.addEventListener('change', () => syncMobileMode(listing, cards))

		const onKitchenHover = (event) => {
			if (isHomeMobile()) {
				return
			}
			const link = event.target.closest(HOME_FABRIC_KITCHEN_LINK_SELECTOR)
			if (!link || !listing.contains(link)) {
				return
			}
			const card = link.closest(HOME_FABRIC_CARD_SELECTOR)
			if (!card || !listing.contains(card)) {
				return
			}
			const state = getCardState(card)
			const linkIndex = getLinkIndex(state, link)
			if (linkIndex >= 0) {
				setCardByIndex(card, linkIndex)
				// logHomeDebug('interaction:kitchen-hover', { linkIndex })
			}
		}

		listing.addEventListener('click', (event) => {
			// logHomeDebug('interaction:card-image-click', {
			// 	targetTag: event.target && event.target.tagName ? event.target.tagName.toLowerCase() : 'unknown',
			// 	insideLink: Boolean(event.target.closest('a')),
			// 	blockedByMobileMode: isHomeMobile(),
			// })

			if (isHomeMobile() || event.target.closest('a')) {
				return
			}

			let card = null
			const toggle = event.target.closest(HOME_FABRIC_TOGGLE_SELECTOR)
			if (toggle && listing.contains(toggle)) {
				card = toggle.closest(HOME_FABRIC_CARD_SELECTOR)
			} else {
				const media = event.target.closest(HOME_FABRIC_MEDIA_SELECTOR)
				if (media && listing.contains(media)) {
					card = media.closest(HOME_FABRIC_CARD_SELECTOR)
				}
			}

			if (!card || !listing.contains(card)) {
				return
			}
			const row = card.closest(HOME_FABRIC_ROW_SELECTOR)
			if (row) {
				setRowExpandedCard(row, card)
			}
		})

		listing.addEventListener('mouseover', onKitchenHover)
		listing.addEventListener('focusin', onKitchenHover)

		listing.dataset.homeFabricsBound = 'true'
		// logHomeDebug('init:done', { cards: cards.length })
	})
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initHomeFabrics)
} else {
	initHomeFabrics()
}
