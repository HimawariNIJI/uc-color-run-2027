/**
 * Hover tooltips for bar and donut chart marks. Every value is also printed on the chart
 * and in its screen-reader table, so the tooltip only adds convenience.
 *
 * Markup: an element with data-chart-tip-value, data-chart-tip-label and data-chart-tip-color.
 * Its [data-chart-tip-target] descendant (or itself) is the hover area.
 */
let tooltip;

function getTooltip() {
    if (tooltip) {
        return tooltip;
    }

    tooltip = document.createElement('div');
    tooltip.className = 'pointer-events-none fixed z-50 rounded-xl border border-border bg-surface px-3 py-2 shadow-popover';
    tooltip.hidden = true;
    tooltip.innerHTML = '<p class="flex items-center gap-2 text-body-sm font-semibold"><span class="h-0.5 w-3 rounded-full" data-key></span><span data-value></span></p><p class="mt-0.5 whitespace-nowrap text-caption text-muted" data-label></p>';
    document.body.append(tooltip);

    return tooltip;
}

function show(source, event) {
    const element = getTooltip();

    // Chart labels can contain participant data, so only ever set them as text.
    element.querySelector('[data-value]').textContent = source.dataset.chartTipValue;
    element.querySelector('[data-label]').textContent = source.dataset.chartTipLabel;
    element.querySelector('[data-key]').style.backgroundColor = source.dataset.chartTipColor;
    element.hidden = false;

    move(event);
}

function move(event) {
    const element = getTooltip();
    const offset = 14;
    const { innerWidth } = window;
    const width = element.offsetWidth;
    const left = event.clientX + offset + width > innerWidth - 8 ? event.clientX - offset - width : event.clientX + offset;

    element.style.left = `${Math.max(8, left)}px`;
    element.style.top = `${Math.max(8, event.clientY - element.offsetHeight - offset)}px`;
}

function hide() {
    if (tooltip) {
        tooltip.hidden = true;
    }
}

export function initChartTooltips(root) {
    root.querySelectorAll('[data-chart-tip-value]').forEach((source) => {
        const target = source.querySelector('[data-chart-tip-target]') ?? source;

        target.addEventListener('pointerenter', (event) => show(source, event));
        target.addEventListener('pointermove', move);
        target.addEventListener('pointerleave', hide);
    });

    window.addEventListener('scroll', hide, { passive: true });
}
