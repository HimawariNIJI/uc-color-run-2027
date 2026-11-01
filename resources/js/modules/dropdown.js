/**
 * Click-to-open menus.
 *
 * Markup: [data-dropdown] wraps a [data-dropdown-trigger] button and a [data-dropdown-menu] panel.
 * The wrapper gets data-open="true" while open, for styling (e.g. rotating a chevron).
 */
const openDropdowns = new Set();

function setOpen(dropdown, open) {
    const trigger = dropdown.querySelector('[data-dropdown-trigger]');
    const menu = dropdown.querySelector('[data-dropdown-menu]');

    trigger.setAttribute('aria-expanded', String(open));
    menu.hidden = !open;
    dropdown.dataset.open = String(open);

    if (open) {
        openDropdowns.add(dropdown);
    } else {
        openDropdowns.delete(dropdown);
    }
}

export function closeAllDropdowns(except = null) {
    openDropdowns.forEach((dropdown) => {
        if (dropdown !== except) {
            setOpen(dropdown, false);
        }
    });
}

export function initDropdowns(root) {
    const dropdowns = root.querySelectorAll('[data-dropdown]');

    if (dropdowns.length === 0) {
        return;
    }

    dropdowns.forEach((dropdown) => {
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        trigger.addEventListener('click', () => {
            const willOpen = menu.hidden;

            closeAllDropdowns(dropdown);
            document.dispatchEvent(new CustomEvent('dropdown:open'));
            setOpen(dropdown, willOpen);
        });

        dropdown.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !menu.hidden) {
                setOpen(dropdown, false);
                trigger.focus();
            }
        });

        dropdown.addEventListener('focusout', (event) => {
            if (!menu.hidden && event.relatedTarget && !dropdown.contains(event.relatedTarget)) {
                setOpen(dropdown, false);
            }
        });
    });

    document.addEventListener('click', (event) => {
        openDropdowns.forEach((dropdown) => {
            if (!dropdown.contains(event.target)) {
                setOpen(dropdown, false);
            }
        });
    });
}
