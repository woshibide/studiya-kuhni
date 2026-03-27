const footerTypedWord = document.querySelector('#footer-typed-word')

if (footerTypedWord) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    const footerWords = [
        ' для большой семьи',
        ' на КМВ',
        ' для жизни',
        ' кавказ купить онлайн',
        '...',
        ' для счастья',
        ' для вас'
    ].filter((word) => word.length > 0)

    const TYPE_DELAY_MS = 123
    const ERASE_DELAY_MS = 82
    const FULL_WORD_PAUSE_MS = 1320
    const EMPTY_WORD_PAUSE_MS = 480
    const TYPE_VARIANCE_MS = 22
    const ERASE_VARIANCE_MS = 14

    const nextDelay = (base, variance) => {
        const swing = Math.round((Math.random() * 2 - 1) * variance)
        return Math.max(16, base + swing)
    }

    const wait = (ms) => new Promise((resolve) => {
        window.setTimeout(resolve, ms)
    })

    const typeWord = async (word) => {
        for (let index = 1; index <= word.length; index += 1) {
            footerTypedWord.textContent = word.slice(0, index)
            await wait(nextDelay(TYPE_DELAY_MS, TYPE_VARIANCE_MS))
        }
    }

    const eraseWord = async (word) => {
        for (let index = word.length - 1; index >= 0; index -= 1) {
            footerTypedWord.textContent = word.slice(0, index)
            await wait(nextDelay(ERASE_DELAY_MS, ERASE_VARIANCE_MS))
        }
    }

    if (reducedMotion || footerWords.length <= 1) {
        footerTypedWord.textContent = footerWords[0] ?? footerTypedWord.textContent
        footerTypedWord.classList.add('is-static')
    } else {
        const cycle = async () => {
            let wordIndex = 0
            footerTypedWord.textContent = ''

            while (true) {
                const activeWord = footerWords[wordIndex]
                footerTypedWord.textContent = ''
                await typeWord(activeWord)
                await wait(FULL_WORD_PAUSE_MS)
                await eraseWord(activeWord)
                await wait(EMPTY_WORD_PAUSE_MS)
                wordIndex = (wordIndex + 1) % footerWords.length
            }
        }

        cycle()
    }
}
