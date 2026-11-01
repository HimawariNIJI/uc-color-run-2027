{{--
    Content of a details modal: title, subtitle, "Close" and a body. The same markup renders
    as a standalone page, where "Close" goes back to the list.
--}}
@props(['title', 'subtitle' => null, 'closeHref'])

<div {{ $attributes }}>
    <header class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-border bg-surface px-5 py-5 sm:px-8 sm:py-6">
        <div class="min-w-0">
            <h2 id="admin-modal-title" class="text-h3 outline-none" tabindex="-1">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-1 text-body-sm text-muted">{{ $subtitle }}</p>
            @endif
        </div>
        <a href="{{ $closeHref }}" class="-mr-2 inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-2 text-body-sm font-semibold text-muted transition-colors hover:bg-foreground/5 hover:text-foreground focus-visible:outline-2 focus-visible:outline-primary" data-admin-modal-close>
            <x-icon name="x" class="size-4.5" />
            Close
        </a>
    </header>

    <div class="px-5 py-6 sm:px-8 sm:py-7">
        {{ $slot }}
    </div>
</div>
