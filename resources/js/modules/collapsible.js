/**
 * Show/hide a single region.
 *
 * Markup: a [data-collapsible-trigger] button whose aria-controls points at the region to toggle.
 */
export function initCollapsibles(root) {
    root.querySelectorAll('[data-collapsible-trigger]').forEach((trigger) => {
        const region = document.getElementById(trigger.getAttribute('aria-controls'));

        if (!region) {
            return;
        }

        trigger.addEventListener('click', () => {
            const expanded = trigger.getAttribute('aria-expanded') === 'true';

            trigger.setAttribute('aria-expanded', String(!expanded));
            region.hidden = expanded;
        });
    });
}
