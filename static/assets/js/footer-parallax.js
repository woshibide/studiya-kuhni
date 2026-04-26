const footerRoot = document.querySelector('footer')
const footerBigText = document.querySelector('#big-footer-text')
const footerDetails = document.querySelector('#footer-details')

if (footerRoot && footerBigText && footerDetails) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)')

    let ticking = false

    const clamp = (value, min, max) => Math.min(max, Math.max(min, value))

    const getHiddenOffset = () => {
        const isMobile = window.matchMedia('(max-width: 48rem)').matches
        const multiplier = isMobile ? 0.7 : 0.9
        return -Math.max(72, Math.round(footerBigText.offsetHeight * multiplier))
    }

    const applyParallax = () => {
        ticking = false

        if (reducedMotion.matches) {
            footerRoot.style.setProperty('--footer-parallax-y', '0px')
            return
        }

        const detailsRect = footerDetails.getBoundingClientRect()
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight

        const revealStart = viewportHeight * 0.96
        const revealEnd = viewportHeight * 0.34
        const rawProgress = (revealStart - detailsRect.bottom) / (revealStart - revealEnd)
        const progress = clamp(rawProgress, 0, 1)

        const hiddenOffset = getHiddenOffset()
        const easedProgress = 1 - Math.pow(1 - progress, 3)
        const y = hiddenOffset * (1 - easedProgress)

        footerRoot.style.setProperty('--footer-parallax-y', `${y.toFixed(1)}px`)
    }

    const requestTick = () => {
        if (ticking) {
            return
        }

        ticking = true
        window.requestAnimationFrame(applyParallax)
    }

    window.addEventListener('scroll', requestTick, { passive: true })
    window.addEventListener('resize', requestTick)

    if (typeof reducedMotion.addEventListener === 'function') {
        reducedMotion.addEventListener('change', requestTick)
    } else if (typeof reducedMotion.addListener === 'function') {
        reducedMotion.addListener(requestTick)
    }

    requestTick()
}
