{{--
    Surface card used for every admin section.

    actions: slot for buttons beside the title (Export CSV, View All, ranges)
    divided: draws the rule between the header and the content, as on the management pages
--}}
@props(['title' => null, 'description' => null, 'divided' => false])

<section {{ $attributes->class('min-w-0 rounded-3xl border border-border bg-surface p-5 shadow-card sm:p-6 lg:p-8') }}>
    @if ($title)
        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
            <div class="min-w-0">
                <h2 class="text-h4 text-primary">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-1 text-body-sm text-muted">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>

        @if ($divided)
            <hr class="my-5 border-border lg:my-6">
        @endif
    @endif

    {{ $slot }}
</section>
