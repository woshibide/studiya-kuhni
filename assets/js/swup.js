document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.Swup !== 'function') return;
    if (!document.querySelector('#swup')) return;
    if (window.__swup) return;

    window.__swup = new window.Swup({
        containers: ['#swup']
    });
});
