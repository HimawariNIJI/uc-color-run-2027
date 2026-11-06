/**
 * Scroll reveal: [data-reveal] elements fade and slide in once, when they enter the viewport.
 * Use data-reveal="clip" for an image-style wipe, and style="--reveal-delay: 120ms" to stagger.
 *
 * The <html> element only gets the js-reveal class when motion is allowed (see the layout),
 * so without it everything is simply visible.
 */
export function initReveal(root) {
    window.revealReady = true;

    if (!document.documentElement.classList.contains('js-reveal')) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0 },
    );

    root.querySelectorAll('[data-reveal]').forEach((element) => observer.observe(element));
}
