@props(['label', 'value', 'caption', 'icon'])

<article {{ $attributes->class('min-w-0 rounded-3xl border border-border bg-surface p-5 shadow-card sm:p-6') }}>
    <div class="flex items-start justify-between gap-3">
        <h2 class="pt-1.5 text-body font-semibold">{{ $label }}</h2>
        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-yellow text-primary" aria-hidden="true">
            <x-icon :name="$icon" class="size-5" />
        </span>
    </div>
    <p class="mt-2 text-h2 text-primary">{{ \App\Support\NumberFormat::integer($value) }}</p>
    <p class="mt-1 text-body-sm text-muted">{{ $caption }}</p>
</article>
