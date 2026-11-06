/**
 * Accordion where only one item per group is open at a time.
 *
 * Markup: [data-accordion] groups [data-accordion-item] elements, each with a
 * [data-accordion-trigger] button (aria-controls → panel id). Open items get [data-open].
 */
function setExpanded(trigger, expanded) {
    const panel = document.getElementById(trigger.getAttribute('aria-controls'));

    trigger.setAttribute('aria-expanded', String(expanded));
    trigger.closest('[data-accordion-item]')?.toggleAttribute('data-open', expanded);

    if (panel) {
        panel.hidden = !expanded;
    }
}

export function initAccordions(root) {
    root.querySelectorAll('[data-accordion]').forEach((group) => {
        const triggers = [...group.querySelectorAll('[data-accordion-trigger]')];

        triggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const willExpand = trigger.getAttribute('aria-expanded') !== 'true';

                triggers.forEach((candidate) => setExpanded(candidate, candidate === trigger && willExpand));
            });
        });
    });
}
