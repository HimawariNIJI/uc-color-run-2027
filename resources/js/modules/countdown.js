/**
 * Live countdown to a moment in time.
 *
 * Markup: [data-countdown="ISO-8601 date"] containing [data-countdown-unit] elements whose value is
 * "days", "hours", "minutes" or "seconds". When no "hours" unit is present, minutes show the total
 * minutes left (useful for short payment windows). The element gets [data-countdown-finished] at zero.
 * Add [data-countdown-animate] to give changing digits a short tick animation.
 */
const pad = (value) => String(value).padStart(2, '0');
const TICK_CLASS = 'motion-safe:animate-tick';

export function initCountdowns(root) {
    root.querySelectorAll('[data-countdown]').forEach((element) => {
        const target = new Date(element.dataset.countdown).getTime();
        const units = [...element.querySelectorAll('[data-countdown-unit]')];

        if (Number.isNaN(target) || units.length === 0) {
            return;
        }

        const hasHours = units.some((unit) => unit.dataset.countdownUnit === 'hours');
        const animate = element.hasAttribute('data-countdown-animate');
        let timer = null;

        const tick = () => {
            const remaining = Math.max(0, Math.floor((target - Date.now()) / 1000));
            const values = {
                days: Math.floor(remaining / 86400),
                hours: Math.floor((remaining % 86400) / 3600),
                minutes: hasHours ? Math.floor((remaining % 3600) / 60) : Math.floor(remaining / 60),
                seconds: remaining % 60,
            };

            units.forEach((unit) => {
                const next = pad(values[unit.dataset.countdownUnit]);

                if (unit.textContent === next) {
                    return;
                }

                unit.textContent = next;

                if (animate) {
                    // Restart the animation on every change.
                    unit.classList.remove(TICK_CLASS);
                    void unit.offsetWidth;
                    unit.classList.add(TICK_CLASS);
                }
            });

            if (remaining === 0) {
                window.clearInterval(timer);
                element.toggleAttribute('data-countdown-finished', true);
            }
        };

        tick();
        timer = window.setInterval(tick, 1000);
    });
}
