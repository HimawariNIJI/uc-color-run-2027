{{--
    Route map inside the Design Division "Map Route" frame (landscape panel + yellow-framed indigo
    screen). On phones the frame's illustration would be too small, so only the framed screen shows.

    src:   route map image URL, or null for the "coming soon" placeholder
--}}
@props(['src' => null, 'alt', 'label'])

<figure {{ $attributes->class('relative isolate aspect-[4/3] overflow-hidden rounded-3xl border-[6px] border-brand-yellow bg-primary md:aspect-[4125/2660] md:overflow-visible md:rounded-none md:border-0 md:bg-transparent') }}>
    <div
        class="absolute inset-0 -z-10 hidden bg-[length:111.56%_auto] bg-[position:55.35%_50%] bg-no-repeat md:block"
        style="background-image: url('{{ \App\Support\BrandAsset::url('Map Route.png') }}')"
        aria-hidden="true"
    ></div>

    {{-- The frame's indigo screen, measured from the artwork. --}}
    <div class="absolute inset-0 overflow-hidden md:left-[32.3%] md:top-[4.5%] md:h-[90.8%] md:w-[65.5%] md:rounded-[4.9%/5.5%]">
        @if ($src)
            <img src="{{ $src }}" alt="{{ $alt }}" class="size-full bg-primary object-contain" loading="lazy" decoding="async">
        @else
            <div class="bg-powder relative flex size-full flex-col items-center justify-center gap-3 p-6 text-center" role="img" aria-label="{{ $alt }}">
                <span class="grid size-14 place-items-center rounded-2xl bg-brand-yellow text-primary shadow-button motion-safe:animate-float">
                    <x-icon name="map" class="size-7" />
                </span>
                <span class="font-display text-h4 text-brand-cream">{{ $label }}</span>
            </div>
        @endif
    </div>
</figure>
