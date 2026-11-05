{{--
    The signature content card from the design: a surface panel with the logo sitting
    on a tab that overlaps its top edge.

    width: "content" (960px) | "form" (768px) | "card" (448px)
--}}
@props(['width' => 'content'])

<section {{ $attributes->class([
    'relative mx-auto mt-8 w-full rounded-3xl border border-border bg-surface px-5 pb-8 pt-14 shadow-card motion-safe:animate-rise sm:px-8 md:px-12 md:pb-12 md:pt-16',
    match ($width) {
        'form' => 'max-w-form',
        'card' => 'max-w-card',
        default => 'max-w-content',
    },
]) }}>
    <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-border bg-surface px-5 py-3 shadow-card">
        <x-logo />
    </div>

    {{ $slot }}
</section>
