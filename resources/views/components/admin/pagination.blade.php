{{--
    "Page 1 of 4" with round page buttons. Phones show the neighbouring pages only;
    larger screens add the first and last page.
--}}
@props(['paginator'])

@php
    $current = $paginator->currentPage();
    $last = max(1, $paginator->lastPage());

    $pages = collect([1, $current - 1, $current, $current + 1, $last])
        ->filter(fn (int $page): bool => $page >= 1 && $page <= $last)
        ->unique()
        ->sort()
        ->values();

    $buttonClasses = 'grid size-9 place-items-center rounded-full text-body-sm font-semibold transition-colors sm:size-10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
@endphp

<nav {{ $attributes->class('mt-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-between') }} aria-label="Navigasi halaman">
    <p class="text-body-sm text-muted">Page {{ $current }} of {{ $last }}</p>

    <ul class="flex items-center gap-1.5 sm:gap-2">
        <li>
            @if ($paginator->onFirstPage())
                <span class="{{ $buttonClasses }} bg-foreground/4 text-muted/50" aria-hidden="true"><x-icon name="chevron-left" class="size-4.5" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="{{ $buttonClasses }} bg-foreground/5 text-foreground hover:bg-primary/10 hover:text-primary" rel="prev" aria-label="Halaman sebelumnya">
                    <x-icon name="chevron-left" class="size-4.5" />
                </a>
            @endif
        </li>

        @foreach ($pages as $index => $page)
            @php($isNeighbour = abs($page - $current) <= 1)

            @if ($index > 0 && $page - $pages[$index - 1] > 1)
                <li class="hidden w-5 text-center text-body-sm text-muted sm:block" aria-hidden="true">…</li>
            @endif

            <li @class(['hidden sm:block' => ! $isNeighbour])>
                @if ($page === $current)
                    <span class="{{ $buttonClasses }} bg-primary text-white" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="{{ $buttonClasses }} bg-foreground/5 text-foreground hover:bg-primary/10 hover:text-primary" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                @endif
            </li>
        @endforeach

        <li>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="{{ $buttonClasses }} bg-foreground/5 text-foreground hover:bg-primary/10 hover:text-primary" rel="next" aria-label="Halaman berikutnya">
                    <x-icon name="chevron-right" class="size-4.5" />
                </a>
            @else
                <span class="{{ $buttonClasses }} bg-foreground/4 text-muted/50" aria-hidden="true"><x-icon name="chevron-right" class="size-4.5" /></span>
            @endif
        </li>
    </ul>
</nav>
