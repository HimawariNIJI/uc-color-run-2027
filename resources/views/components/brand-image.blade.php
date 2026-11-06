{{--
    A Design Division image from public/assets/images, for example
    <x-brand-image file="Asset 17@3x.png" class="w-64" />.

    Decorative by default (hidden from screen readers). Pass alt to make it meaningful.
    eager: load immediately (above the fold); otherwise the browser loads it lazily.
--}}
@props(['file', 'alt' => null, 'eager' => false])

@php([$width, $height] = \App\Support\BrandAsset::dimensions($file))

<img
    src="{{ \App\Support\BrandAsset::url($file) }}"
    width="{{ $width }}"
    height="{{ $height }}"
    alt="{{ $alt ?? '' }}"
    @if ($alt === null) aria-hidden="true" @endif
    loading="{{ $eager ? 'eager' : 'lazy' }}"
    decoding="async"
    draggable="false"
    {{ $attributes->class('pointer-events-none select-none h-auto max-w-none') }}
>
