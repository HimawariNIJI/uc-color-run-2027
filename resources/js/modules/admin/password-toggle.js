/**
 * Show/hide password buttons.
 *
 * Markup: <button data-password-toggle aria-controls="password-input-id" data-label-show data-label-hide>.
 */
export function initPasswordToggles(root) {
    root.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));

        if (!input) {
            return;
        }

        button.addEventListener('click', () => {
            const reveal = input.type === 'password';

            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(reveal));
            button.textContent = reveal ? button.dataset.labelHide : button.dataset.labelShow;
            input.focus();
        });
    });
}
