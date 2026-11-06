/**
 * Horizontal scroll-snap carousel with previous/next buttons.
 *
 * Markup: [data-carousel] contains a [data-carousel-track] (scrollable list) plus
 * [data-carousel-prev] and [data-carousel-next] buttons.
 */
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

export function initCarousels(root) {
    root.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-carousel-track]');
        const previous = carousel.querySelector('[data-carousel-prev]');
        const next = carousel.querySelector('[data-carousel-next]');

        if (!track || !previous || !next) {
            return;
        }

        const stepSize = () => {
            const item = track.firstElementChild;
            const gap = parseFloat(getComputedStyle(track).columnGap) || 0;

            return item ? item.getBoundingClientRect().width + gap : track.clientWidth;
        };

        const scrollByStep = (direction) => {
            track.scrollBy({
                left: direction * stepSize(),
                behavior: prefersReducedMotion.matches ? 'auto' : 'smooth',
            });
        };

        const updateButtons = () => {
            const maxScroll = track.scrollWidth - track.clientWidth - 1;

            previous.disabled = track.scrollLeft <= 1;
            next.disabled = track.scrollLeft >= maxScroll;
        };

        previous.addEventListener('click', () => scrollByStep(-1));
        next.addEventListener('click', () => scrollByStep(1));
        track.addEventListener('scroll', () => window.requestAnimationFrame(updateButtons), { passive: true });
        new ResizeObserver(updateButtons).observe(track);

        updateButtons();
    });
}
