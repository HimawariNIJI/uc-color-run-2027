{{--
    Section title with an optional eyebrow and description, finished with a brand brush swirl.
    lined:  draws short rules either side of the eyebrow ("— OUR VALUES —").
    tone:   "dark" (on cream and white) | "light" (on indigo sections)
--}}
@props(['title', 'eyebrow' => null, 'description' => null, 'align' => 'center', 'lined' => false, 'uppercase' => true, 'tone' => 'dark'])

@php($isLight = $tone === 'light')

<div {{ $attributes->class(['flex flex-col gap-3', $align === 'center' ? 'items-center text-center' : 'items-start text-left']) }} data-reveal>
    @if ($eyebrow)
        <p @class(['flex items-center gap-3 text-caption font-semibold uppercase tracking-[0.18em]', 'text-brand-yellow' => $isLight, 'text-secondary-strong' => ! $isLight])>
            @if ($lined)<span class="h-0.5 w-8 rounded-full bg-current opacity-50 sm:w-14" aria-hidden="true"></span>@endif
            {{ $eyebrow }}
            @if ($lined)<span class="h-0.5 w-8 rounded-full bg-current opacity-50 sm:w-14" aria-hidden="true"></span>@endif
        </p>
    @endif
    <div @class(['flex flex-col', $align === 'center' ? 'items-center' : 'items-start'])>
        <h2 @class(['text-h2', 'uppercase' => $uppercase, 'text-brand-cream' => $isLight, 'text-primary' => ! $isLight])>{{ $title }}</h2>
        <x-brand-image file="4@3x.png" class="-mt-1 w-28 sm:w-36" />
    </div>
    @if ($description)
        <p @class(['max-w-2xl text-body', 'text-brand-cream/80' => $isLight, 'text-muted' => ! $isLight])>{{ $description }}</p>
    @endif
</div>
