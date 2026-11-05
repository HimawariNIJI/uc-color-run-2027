{{--
    Centered icon + title + message block for empty, success and failure states.

    tone: "primary" | "success" | "error" | "neutral"
--}}
@props(['icon', 'title', 'tone' => 'primary', 'heading' => 'h2'])

@php
    $iconClasses = match ($tone) {
        'success' => 'bg-success/12 text-success-strong',
        'error' => 'bg-error/10 text-error',
        'neutral' => 'bg-foreground/6 text-muted',
        default => 'bg-primary/10 text-primary',
    };
@endphp

<div {{ $attributes->class('flex flex-col items-center text-center') }}>
    <span class="grid size-14 place-items-center rounded-2xl {{ $iconClasses }}">
        <x-icon :name="$icon" class="size-7" />
    </span>
    <{{ $heading }} class="mt-4 font-display text-h3 font-normal text-primary">{{ $title }}</{{ $heading }}>
    <div class="mt-2 max-w-md text-body text-muted">{{ $slot }}</div>
</div>
