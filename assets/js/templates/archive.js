const shell = document.querySelector('.archive-layout-shell');
const posts = document.querySelectorAll('.archive-post-entry');
const archiveReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const archiveIsPhone = window.matchMedia('(max-width: 48rem)').matches;

if (shell && posts.length > 0 && !archiveReducedMotion && !archiveIsPhone) {
    shell.addEventListener('scroll', () => {
        const shellRect = shell.getBoundingClientRect();
        
        posts.forEach((post) => {
            const rect = post.getBoundingClientRect();
            const postTopRelToShell = rect.top - shellRect.top;
            
            const rawProgress = postTopRelToShell / rect.height; // -1 to 1
            const progress = Math.min(Math.abs(rawProgress), 1);
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const opacity = easeOut * 1.85;

            post.style.setProperty('--scroll-progress', rawProgress);
            
            const overlay = post.querySelector('.archive-post-overlay');
            if (overlay) {
                overlay.style.opacity = opacity;
                
                // offset background position 0% to 100% for parallax gradient
                const bgPos = 50 - (Math.max(Math.min(rawProgress, 1), -1) * 50);
                overlay.style.backgroundPosition = `50% ${bgPos}%`;
            }
        });
    }, { passive: true });
}

