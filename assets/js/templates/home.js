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