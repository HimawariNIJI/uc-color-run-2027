/**
 * Photo pickers with a preview (resources/views/components/form/image-upload.blade.php).
 *
 * Markup: [data-image-upload] (with data-max-bytes, data-types, data-type-error, data-size-error)
 * contains the file input [data-image-upload-input], the box shown before a photo is chosen
 * [data-image-upload-empty], the preview box [data-image-upload-selected] with its
 * [data-image-upload-preview] image and [data-image-upload-name], and [data-image-upload-error].
 *
 * Type and size are checked here only to spare a wasted upload; the server checks the file again.
 * The preview always matches what the input will send: a refused or cancelled choice empties it.
 */
export function initImageUploads(root) {
    root.querySelectorAll('[data-image-upload]').forEach((field) => {
        const input = field.querySelector('[data-image-upload-input]');
        const empty = field.querySelector('[data-image-upload-empty]');
        const selected = field.querySelector('[data-image-upload-selected]');
        const preview = field.querySelector('[data-image-upload-preview]');
        const fileName = field.querySelector('[data-image-upload-name]');
        const clientError = field.querySelector('[data-image-upload-error]');
        const reminder = field.querySelector('[data-image-upload-reminder]');

        if (!input || !empty || !selected || !preview || !clientError) {
            return;
        }

        const serverError = document.getElementById(`${input.id}-error`);
        const maxBytes = Number(field.dataset.maxBytes);
        const types = (field.dataset.types ?? '').split(',');
        let previewUrl = null;

        const showChoice = (file) => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            if (file) {
                previewUrl = URL.createObjectURL(file);
                preview.src = previewUrl;
                fileName.textContent = `${file.name} · ${formatSize(file.size)}`;
            }

            selected.hidden = !file;
            empty.hidden = Boolean(file);
        };

        const showError = (message) => {
            clientError.querySelector('[data-image-upload-error-text]').textContent = message ?? '';
            clientError.hidden = !message;
            empty.classList.toggle('border-error', Boolean(message));
            empty.classList.toggle('border-border', !message);

            if (message) {
                input.setAttribute('aria-invalid', 'true');
                input.setAttribute('aria-describedby', clientError.id);
            } else {
                input.removeAttribute('aria-invalid');
                input.removeAttribute('aria-describedby');
            }
        };

        input.addEventListener('change', () => {
            const file = input.files?.[0] ?? null;

            if (!file) {
                showChoice(null);

                return;
            }

            // A new choice replaces whatever the server said about the previous one.
            if (serverError) {
                serverError.hidden = true;
            }

            if (reminder) {
                reminder.hidden = true;
            }

            const problem = !types.includes(file.type)
                ? field.dataset.typeError
                : file.size > maxBytes
                  ? field.dataset.sizeError
                  : null;

            if (problem) {
                input.value = '';
                showChoice(null);
                showError(problem);

                return;
            }

            showError(null);
            showChoice(file);
        });
    });
}

function formatSize(bytes) {
    return bytes >= 1024 * 1024
        ? `${(bytes / (1024 * 1024)).toLocaleString('id-ID', { maximumFractionDigits: 1 })} MB`
        : `${Math.max(1, Math.round(bytes / 1024))} KB`;
}
