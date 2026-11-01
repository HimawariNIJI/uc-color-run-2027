/**
 * Close buttons for alerts.
 *
 * Markup: a [data-dismiss] button inside a [data-dismissible] element.
 */
export function initDismissibles(root) {
    root.querySelectorAll('[data-dismissible] [data-dismiss]').forEach((button) => {
        button.addEventListener('click', () => button.closest('[data-dismissible]').remove());
    });
}
