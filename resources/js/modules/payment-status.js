/**
 * Payment page polling.
 *
 * Markup: [data-payment-status="status URL"]. Every few seconds the page asks the server, which
 * checks with Midtrans, and moves to the success or failed page as soon as the payment is settled.
 * The server decides the status; this script only follows the redirect it returns.
 */
const INTERVAL_MS = 5000;

export function initPaymentStatus(root) {
    const element = root.querySelector('[data-payment-status]');

    if (!element) {
        return;
    }

    const url = element.dataset.paymentStatus;
    let stopped = false;

    const poll = async () => {
        if (stopped || document.hidden) {
            return;
        }

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });

            if (response.ok) {
                const { redirect } = await response.json();

                if (redirect) {
                    stopped = true;
                    window.location.assign(redirect);
                }
            }
        } catch {
            // A missed poll is fine; the next one (or the "Cek Status" button) tries again.
        }
    };

    const timer = window.setInterval(poll, INTERVAL_MS);
    window.addEventListener('pagehide', () => {
        stopped = true;
        window.clearInterval(timer);
    });
}
