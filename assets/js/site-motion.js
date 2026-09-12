(() => {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const listeners = new Set();
    const paused = () => reduced.matches;
    const notify = () => {
        document.documentElement.classList.toggle('is-motion-paused', paused());
        listeners.forEach((listener) => listener(paused() || document.hidden));
    };
    window.studioMotion = {
        get paused() { return paused() || document.hidden; },
        subscribe(listener) {
            listeners.add(listener);
            listener(this.paused);
            return () => listeners.delete(listener);
        },
        typing(element, words, { typeDelay = 143, eraseDelay = 82, hold = 1500 } = {}) {
            if (!element || !words.length) return;
            let timer;
            let index = 0;
            let length = words[0].length;
            let erasing = true;
            const tick = () => {
                const word = words[index];
                if (erasing) {
                    length -= 1;
                    if (length <= 0) { erasing = false; index = (index + 1) % words.length; length = 0; }
                } else {
                    length += 1;
                    if (length >= word.length) erasing = true;
                }
                element.textContent = words[index].slice(0, length);
                timer = window.setTimeout(tick, length === words[index].length ? hold : erasing ? eraseDelay : typeDelay);
            };
            return this.subscribe((isPaused) => {
                window.clearTimeout(timer);
                element.classList.toggle('is-static', isPaused || words.length < 2);
                element.textContent = words[index];
                length = words[index].length;
                erasing = true;
                if (!isPaused && words.length > 1) timer = window.setTimeout(tick, hold);
            });
        },
    };
    reduced.addEventListener('change', notify);
    document.addEventListener('visibilitychange', notify);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', notify);
    notify();
})();
