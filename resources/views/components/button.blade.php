{{--
    Renders an <a> when href is given, otherwise a <button>.

    variant: "primary" (yellow brand pill) | "secondary" (outlined) | "ghost"
    size:    "sm" (40px) | "md" (48px) | "lg" (56px)
    pill:    fully rounded corners (default, as in the brand button); false for rounded rectangles
    block:   full width
--}}
@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'pill' => true,
    'block' => false,
])

@php
    $classes = [
        'inline-flex items-center justify-center gap-2 text-center text-button select-none',
        'transition-[translate,box-shadow,background-color,border-color,color] duration-200 ease-spring',
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
            'secondary' => 'border-2 border-primary/15 bg-surface text-primary hover:-translate-y-px hover:border-primary hover:bg-brand-yellow/35',
            'ghost' => 'text-primary hover:bg-primary/6',
            // The Design Division button: a yellow pill riding on an orange track that compresses when pressed.
            default => 'bg-brand-yellow text-primary shadow-button hover:-translate-y-0.5 hover:shadow-button-hover active:translate-y-0.5 active:shadow-button-pressed',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
