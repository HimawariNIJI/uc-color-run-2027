/**
 * Crosshair tooltip for line charts: follows the pointer (or a finger), snapping to the
 * nearest day, and can be moved with the arrow keys when the chart has focus.
 *
 * Markup: see resources/views/components/admin/charts/line.blade.php.
 */
export function initLineCharts(root) {
    root.querySelectorAll('[data-line-chart]').forEach((chart) => {
        const points = JSON.parse(chart.dataset.points);
        const yMax = Number(chart.dataset.yMax) || 1;
        const unit = chart.dataset.unit;
        const crosshair = chart.querySelector('[data-line-chart-crosshair]');
        const dot = chart.querySelector('[data-line-chart-dot]');
        const tooltip = chart.querySelector('[data-line-chart-tooltip]');
        const value = chart.querySelector('[data-line-chart-value]');
        const label = chart.querySelector('[data-line-chart-label]');
        const lastIndex = points.length - 1;
        let activeIndex = null;

        const show = (index) => {
            activeIndex = Math.min(Math.max(index, 0), lastIndex);

            const point = points[activeIndex];
            const x = lastIndex > 0 ? activeIndex / lastIndex : 0.5;
            const y = 1 - point.value / yMax;

            crosshair.style.left = `${x * 100}%`;
            dot.style.left = `${x * 100}%`;
            dot.style.top = `${y * 100}%`;
            value.textContent = `${point.formatted} ${unit}`;
            label.textContent = point.label;
            [crosshair, dot, tooltip].forEach((element) => (element.hidden = false));

            // Keep the tooltip beside the crosshair and inside the chart.
            const width = chart.clientWidth;
            const pointerX = x * width;
            const tooltipWidth = tooltip.offsetWidth;
            const left = pointerX + 12 + tooltipWidth > width ? pointerX - 12 - tooltipWidth : pointerX + 12;

            tooltip.style.left = `${Math.max(0, left)}px`;
        };

        const hide = () => {
            [crosshair, dot, tooltip].forEach((element) => (element.hidden = true));
        };

        const showAtPointer = (event) => {
            const rect = chart.getBoundingClientRect();
            const ratio = Math.min(Math.max((event.clientX - rect.left) / rect.width, 0), 1);

            show(Math.round(ratio * lastIndex));
        };

        chart.addEventListener('pointermove', showAtPointer);
        chart.addEventListener('pointerdown', showAtPointer);
        chart.addEventListener('pointerleave', (event) => {
            if (event.pointerType === 'mouse' && document.activeElement !== chart) {
                hide();
            }
        });

        chart.addEventListener('focus', () => show(activeIndex ?? lastIndex));
        chart.addEventListener('blur', hide);
        chart.addEventListener('keydown', (event) => {
            const moves = { ArrowLeft: -1, ArrowRight: 1 };

            if (event.key in moves) {
                event.preventDefault();
                show((activeIndex ?? lastIndex) + moves[event.key]);
            } else if (event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                show(event.key === 'Home' ? 0 : lastIndex);
            } else if (event.key === 'Escape') {
                hide();
            }
        });
    });
}
