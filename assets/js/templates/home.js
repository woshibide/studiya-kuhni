(() => {
    const element = document.querySelector('#home-hero-typed');
    if (!element || !window.studioMotion) return;
    let words = [];
    try {
        const parsed = JSON.parse(element.dataset.words || '[]');
        if (Array.isArray(parsed)) words = parsed.map(String).filter(Boolean);
    } catch (_) { /* Keep the server-rendered heading if content is malformed. */ }
    window.studioMotion.typing(element, words);
})();
