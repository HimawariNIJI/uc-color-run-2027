{{--
    Renders an <a> when href is given, otherwise a <button>.

    variant: "primary" | "secondary" | "ghost"
    size:    "sm" (40px) | "md" (48px) | "lg" (56px)
    pill:    fully rounded corners
    block:   full width
--}}
@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'pill' => false,
    'block' => false,
])

@php
    $classes = [
        'inline-flex items-center justify-center gap-2 text-center text-button transition-colors duration-150 select-none',
        $block ? '' : 'whitespace-nowrap',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
        'disabled:pointer-events-none disabled:opacity-60 aria-busy:cursor-progress',
        match ($size) {
            'sm' => 'min-h-10 px-4',
            'lg' => 'min-h-14 px-7',
            default => 'min-h-12 px-6',
        },
        $pill ? 'rounded-full' : 'rounded-xl',
        $block ? 'w-full' : '',
        match ($variant) {
            'secondary' => 'border border-border bg-surface text-foreground hover:border-foreground/25 hover:bg-background',
            'ghost' => 'text-foreground hover:bg-foreground/5',
            default => 'bg-primary text-white shadow-sm shadow-primary/25 hover:bg-primary-strong',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
