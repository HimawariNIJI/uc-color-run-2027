{{--
    Shared dialog for "View" details. Links marked [data-admin-modal-link] load their page's
    "details" fragment into it; without JavaScript the same links open the full page.
--}}
<dialog
    id="admin-modal"
    class="m-0 h-dvh max-h-none w-full max-w-none overflow-y-auto overscroll-contain bg-surface p-0 text-foreground backdrop:bg-foreground/45 sm:m-auto sm:h-fit sm:max-h-[min(90dvh,60rem)] sm:max-w-2xl sm:rounded-3xl sm:shadow-popover"
    aria-labelledby="admin-modal-title"
    data-admin-modal
>
    <div data-admin-modal-body></div>

    <template data-admin-modal-loading>
        <div class="flex min-h-72 flex-col items-center justify-center gap-3 p-8 text-body-sm text-muted" role="status">
            <span class="size-8 animate-spin rounded-full border-3 border-primary/20 border-t-primary" aria-hidden="true"></span>
            Memuat data…
        </div>
    </template>

    <template data-admin-modal-error>
        <div class="flex min-h-72 flex-col items-center justify-center gap-4 p-8 text-center" role="alert">
            <span class="grid size-12 place-items-center rounded-2xl bg-error/10 text-error">
                <x-icon name="circle-alert" class="size-6" />
            </span>
            <p id="admin-modal-title" class="text-body font-semibold">Data gagal dimuat.</p>
            <p class="max-w-xs text-body-sm text-muted">Periksa koneksi internet lalu coba lagi.</p>
            <div class="flex gap-2">
                <x-button variant="secondary" size="sm" pill data-admin-modal-close>Close</x-button>
                <x-button size="sm" pill data-admin-modal-retry>Coba lagi</x-button>
            </div>
        </div>
    </template>
</dialog>
