{{--
    Section title with an optional eyebrow and description.
    lined: draws short rules either side of the eyebrow ("— OUR VALUES —").
--}}
@props(['title', 'eyebrow' => null, 'description' => null, 'align' => 'center', 'lined' => false, 'uppercase' => true])

<div {{ $attributes->class(['flex flex-col gap-3', $align === 'center' ? 'items-center text-center' : 'items-start text-left']) }}>
    @if ($eyebrow)
        <p class="flex items-center gap-3 text-caption font-semibold uppercase tracking-[0.18em] text-primary">
            @if ($lined)<span class="h-px w-8 bg-primary/40 sm:w-14" aria-hidden="true"></span>@endif
            {{ $eyebrow }}
            @if ($lined)<span class="h-px w-8 bg-primary/40 sm:w-14" aria-hidden="true"></span>@endif
        </p>
    @endif
    <h2 @class(['text-h2', 'uppercase' => $uppercase])>{{ $title }}</h2>
    @if ($description)
        <p class="max-w-2xl text-body text-muted">{{ $description }}</p>
    @endif
</div>
