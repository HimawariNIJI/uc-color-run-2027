{{--
    tone: "success" | "warning" | "error" | "info" | "neutral"
--}}
@props(['tone' => 'neutral'])

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-caption font-semibold',
    match ($tone) {
        'success' => 'bg-success/12 text-success-strong',
        'warning' => 'bg-warning/15 text-warning-strong',
        'error' => 'bg-error/10 text-error',
        'info' => 'bg-info/10 text-info',
        default => 'bg-foreground/6 text-muted',
    },
]) }}>
    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $slot }}
</span>
