/**
 * Prevent double submissions.
 *
 * Markup: <form data-submit-guard>. On submit, its submit buttons are disabled and marked busy.
 * A button's optional data-busy-label replaces its text while the request is in flight.
 */
function setBusy(form, busy) {
    form.dataset.submitting = String(busy);

    form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.toggleAttribute('aria-busy', busy);

        if (button.dataset.busyLabel) {
            button.dataset.idleLabel ??= button.textContent;
            button.textContent = busy ? button.dataset.busyLabel : button.dataset.idleLabel;
        }

        // Disable after the submit event so the clicked button's name/value is still sent.
        window.setTimeout(() => {
            button.disabled = busy;
        });
    });
}

export function initSubmitGuards(root) {
    const forms = root.querySelectorAll('form[data-submit-guard]');

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();

                return;
            }

            setBusy(form, true);
        });
    });

    // Restore the buttons when the page comes back from the back/forward cache.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            forms.forEach((form) => setBusy(form, false));
        }
    });
}
