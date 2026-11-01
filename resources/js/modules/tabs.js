/**
 * Accessible tabs with arrow-key navigation.
 *
 * Markup: [data-tabs] contains [role="tab"] buttons (aria-controls → [role="tabpanel"] id).
 * Add data-tabs-param="name" to keep the selected tab's data-tab-value in the URL query string.
 */
const KEY_OFFSETS = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 };

export function initTabs(root) {
    root.querySelectorAll('[data-tabs]').forEach((container) => {
        const tabs = [...container.querySelectorAll('[role="tab"]')];
        const param = container.dataset.tabsParam;

        const select = (tab, { focus = false } = {}) => {
            tabs.forEach((candidate) => {
                const selected = candidate === tab;
                const panel = document.getElementById(candidate.getAttribute('aria-controls'));

                candidate.setAttribute('aria-selected', String(selected));
                candidate.tabIndex = selected ? 0 : -1;

                if (panel) {
                    panel.hidden = !selected;
                }
            });

            if (focus) {
                tab.focus();
            }

            if (param && tab.dataset.tabValue) {
                const url = new URL(window.location.href);
                url.searchParams.set(param, tab.dataset.tabValue);
                window.history.replaceState(null, '', url);
            }
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => select(tab));

            tab.addEventListener('keydown', (event) => {
                let target = null;

                if (event.key in KEY_OFFSETS) {
                    target = tabs[(index + KEY_OFFSETS[event.key] + tabs.length) % tabs.length];
                } else if (event.key === 'Home') {
                    target = tabs[0];
                } else if (event.key === 'End') {
                    target = tabs[tabs.length - 1];
                }

                if (target) {
                    event.preventDefault();
                    select(target, { focus: true });
                }
            });
        });
    });
}
