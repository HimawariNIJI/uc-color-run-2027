{{--
    Placeholder wordmarks until the official logo files are supplied.
    Swap the markup for an <img> when the brand assets are ready.

    variant: "event" (UC Color Run 2027) | "organizer" (Universitas Ciputra) | "mark" (event symbol only)
    size:    "sm" | "md" | "lg" | "responsive" (sm on phones, md from 400px up)
--}}
@props(['variant' => 'event', 'size' => 'md'])

@php
    $markSize = match ($size) {
        'sm' => 'size-7',
        'lg' => 'size-12',
        'responsive' => 'size-7 min-[25rem]:size-9',
        default => 'size-9',
    };
    $titleSize = match ($size) {
        'sm' => 'text-[0.9375rem]',
        'lg' => 'text-[1.5rem]',
        'responsive' => 'text-[0.9375rem] min-[25rem]:text-[1.0625rem]',
        default => 'text-[1.0625rem]',
    };
    $subtitleSize = match ($size) {
        'sm' => 'text-[0.5625rem]',
        'lg' => 'text-[0.75rem]',
        'responsive' => 'text-[0.5625rem] min-[25rem]:text-[0.625rem]',
        default => 'text-[0.625rem]',
    };
@endphp

@if ($variant === 'mark')
    <svg {{ $attributes->class([$markSize, 'shrink-0']) }} viewBox="0 0 40 40" aria-hidden="true" focusable="false">
        <circle class="fill-primary" cx="15" cy="15" r="10" />
        <circle class="fill-secondary" cx="26.5" cy="14" r="8" fill-opacity="0.9" />
        <circle class="fill-accent-cyan" cx="25" cy="26.5" r="9" fill-opacity="0.9" />
        <circle class="fill-accent-yellow" cx="12.5" cy="27" r="6.5" fill-opacity="0.95" />
    </svg>
@elseif ($variant === 'organizer')
    <span {{ $attributes->class('inline-flex items-center gap-2.5 text-foreground') }}>
        <span class="{{ $markSize }} grid shrink-0 place-items-center rounded-lg bg-foreground text-[0.8125rem] font-extrabold tracking-tight text-white" aria-hidden="true">UC</span>
        <span class="flex flex-col leading-none">
            <span class="{{ $subtitleSize }} font-semibold uppercase tracking-[0.18em] text-muted">Universitas</span>
            <span class="{{ $titleSize }} mt-1 font-bold tracking-tight">Ciputra</span>
        </span>
    </span>
@else
    <span {{ $attributes->class('inline-flex items-center gap-2.5 text-foreground') }}>
        <svg class="{{ $markSize }} shrink-0" viewBox="0 0 40 40" aria-hidden="true" focusable="false">
            <circle class="fill-primary" cx="15" cy="15" r="10" />
            <circle class="fill-secondary" cx="26.5" cy="14" r="8" fill-opacity="0.9" />
            <circle class="fill-accent-cyan" cx="25" cy="26.5" r="9" fill-opacity="0.9" />
            <circle class="fill-accent-yellow" cx="12.5" cy="27" r="6.5" fill-opacity="0.95" />
        </svg>
        <span class="flex flex-col leading-none">
            <span class="{{ $titleSize }} font-extrabold tracking-tight whitespace-nowrap">UC COLOR RUN</span>
            <span class="{{ $subtitleSize }} mt-1 font-bold tracking-[0.42em] text-primary">2027</span>
        </span>
    </span>
@endif
