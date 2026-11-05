{{--
    Large selectable option used in the registration steps (type, price type and distance).

    status:   small pill under the title, for example "Sisa 12 slot" or "Sold out"
    disabled: shows the option without a link, for options that cannot be picked
--}}
@props(['href' => null, 'title', 'description' => null, 'icon' => null, 'meta' => null, 'status' => null, 'disabled' => false])

@php
    $classes = [
        'relative flex min-h-32 flex-col items-center justify-center gap-2 rounded-2xl border-2 border-primary/10 bg-background px-6 py-7 text-center sm:px-14 md:min-h-36',
        $disabled
            ? 'cursor-not-allowed opacity-60'
            : 'group transition duration-200 ease-spring hover:-translate-y-1 hover:border-primary hover:bg-primary hover:text-brand-cream hover:shadow-[0_6px_0_0_var(--color-brand-orange)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transform-none',
    ];
@endphp

@if ($disabled)
    <div {{ $attributes->class($classes) }} aria-disabled="true">
@else
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
@endif
    @if ($icon)
        <span class="grid size-11 place-items-center rounded-xl bg-brand-yellow text-primary transition-transform duration-200 group-hover:-rotate-6 group-hover:scale-110">
            <x-icon :name="$icon" class="size-5.5" />
        </span>
    @endif

    <span class="font-display text-h3 font-normal uppercase tracking-wide text-primary transition-colors group-hover:text-brand-yellow">{{ $title }}</span>

    @if ($description)
        <span class="max-w-md text-body-sm text-muted transition-colors group-hover:text-brand-cream/85">{{ $description }}</span>
    @endif

    @if ($meta)
        <span @class([
            'text-body-sm font-semibold transition-colors',
            'text-muted' => $disabled,
            'text-secondary-strong group-hover:text-brand-yellow' => ! $disabled,
        ])>{{ $meta }}</span>
    @endif

    @if ($status)
        <span @class([
            'rounded-full px-3 py-1 text-caption font-semibold transition-colors',
            'bg-foreground/8 text-muted' => $disabled,
            'bg-success/12 text-success-strong group-hover:bg-white/15 group-hover:text-brand-cream' => ! $disabled,
        ])>{{ $status }}</span>
    @endif

    @unless ($disabled)
        <x-icon name="arrow-right" class="absolute right-6 top-1/2 hidden size-5 -translate-y-1/2 text-muted transition group-hover:translate-x-1 group-hover:text-brand-yellow sm:block" />
    @endunless
@if ($disabled)
    </div>
@else
    </a>
@endif
