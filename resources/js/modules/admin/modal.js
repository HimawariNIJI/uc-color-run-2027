/**
 * Opens "View" details in the shared admin dialog instead of navigating away.
 *
 * Markup: links with [data-admin-modal-link] point at a full details page. The request adds
 * an X-Admin-Modal header, and the server answers with just that page's "details" fragment.
 * Elements with [data-admin-modal-close] inside the dialog close it.
 */
export function initAdminModal(root) {
    const dialog = root.querySelector('[data-admin-modal]');

    if (!dialog) {
        return;
    }

    const body = dialog.querySelector('[data-admin-modal-body]');
    const loadingTemplate = dialog.querySelector('[data-admin-modal-loading]');
    const errorTemplate = dialog.querySelector('[data-admin-modal-error]');
    let opener = null;
    let currentUrl = null;
    let controller = null;

    const render = (template) => body.replaceChildren(template.content.cloneNode(true));

    const load = async (url) => {
        controller?.abort();
        controller = new AbortController();
        currentUrl = url;
        dialog.setAttribute('aria-busy', 'true');
        render(loadingTemplate);

        try {
            const response = await fetch(url, {
                headers: { 'X-Admin-Modal': '1', Accept: 'text/html' },
                credentials: 'same-origin',
                signal: controller.signal,
            });

            // A redirect means the session expired: let the full page send the admin to login.
            if (response.redirected || response.status === 401) {
                window.location.assign(url);

                return;
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            // The fragment is server-rendered Blade with all values escaped.
            body.innerHTML = await response.text();
            dialog.scrollTop = 0;
            dialog.querySelector('#admin-modal-title')?.focus();
        } catch (error) {
            if (error.name !== 'AbortError') {
                render(errorTemplate);
            }
        } finally {
            dialog.removeAttribute('aria-busy');
        }
    };

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest('a[data-admin-modal-link]');

        if (link) {
            event.preventDefault();

            if (!dialog.open) {
                opener = link;
                dialog.showModal();
            }

            load(link.href);

            return;
        }

        if (dialog.contains(event.target)) {
            if (event.target.closest('[data-admin-modal-close]')) {
                event.preventDefault();
                dialog.close();
            } else if (event.target.closest('[data-admin-modal-retry]')) {
                load(currentUrl);
            } else if (event.target === dialog) {
                // A click on the backdrop lands on the dialog element itself.
                dialog.close();
            }
        }
    });

    dialog.addEventListener('close', () => {
        controller?.abort();
        body.replaceChildren();
        opener?.focus();
        opener = null;
    });
}
