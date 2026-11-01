import { closeAllDropdowns } from './dropdown';

/**
 * Full-screen navigation for mobile and tablet.
 *
 * Markup: a [data-mobile-menu-toggle] button (aria-controls → menu id) containing
 * [data-icon-open] and [data-icon-close] icons.
 */
export function initMobileMenu(root) {
    const toggle = root.querySelector('[data-mobile-menu-toggle]');
    const menu = toggle ? document.getElementById(toggle.getAttribute('aria-controls')) : null;

    if (!toggle || !menu) {
        return;
    }

    const openIcon = toggle.querySelector('[data-icon-open]');
    const closeIcon = toggle.querySelector('[data-icon-close]');

    const setOpen = (open) => {
        menu.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? toggle.dataset.labelClose : toggle.dataset.labelOpen);
        // Icons are SVG elements, which have no `hidden` property, so toggle the attribute.
        openIcon.toggleAttribute('hidden', open);
        closeIcon.toggleAttribute('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
    };

    toggle.addEventListener('click', () => {
        closeAllDropdowns();
        setOpen(menu.hidden);
    });

    document.addEventListener('dropdown:open', () => setOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !menu.hidden) {
            setOpen(false);
            toggle.focus();
        }
    });

    window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
        if (event.matches) {
            setOpen(false);
        }
    });
}
