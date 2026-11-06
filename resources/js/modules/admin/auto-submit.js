/**
 * Filter forms that apply as soon as a dropdown changes.
 *
 * Markup: <form data-auto-submit>. Empty fields are left out of the URL so links stay short.
 */
export function initAutoSubmitForms(root) {
    const forms = root.querySelectorAll('form[data-auto-submit]');

    forms.forEach((form) => {
        form.addEventListener('change', (event) => {
            if (event.target.matches('select')) {
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', () => {
            form.querySelectorAll('input[name], select[name]').forEach((field) => {
                if (field.value.trim() === '') {
                    field.disabled = true;
                }
            });
        });
    });

    // Re-enable the fields when the page is restored from the back/forward cache.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            forms.forEach((form) => form.querySelectorAll('[name]').forEach((field) => (field.disabled = false)));
        }
    });
}
