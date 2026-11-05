{{--
    Logos.

    variant: "event" — the official UC Run logo (public/assets/images/UC Run Logo.PNG) with the
             event name "UC Color Run" / "2027" on its right; used for the main event branding.
             "mark" — the official logo alone, where a heading already names the event.
             "organizer" — the official Universitas Ciputra logo (public/assets/images/UC Logo.png)
             with "Universitas" / "Ciputra" on its right; used in the footer.
    size:    "sm" | "md" | "lg" | "xl" | "responsive" (smaller on phones, larger in the desktop header)
    tone:    "dark" (on cream and white) | "light" (on the indigo header, footer and sidebar)
--}}
@props(['variant' => 'event', 'size' => 'md', 'tone' => 'dark', 'alt' => 'UC Color Run 2027'])

@php
    $isLight = $tone === 'light';
@endphp

@if ($variant === 'organizer')
    @php
        [$sealWidth, $sealHeight] = \App\Support\BrandAsset::dimensions('UC Logo.png');

        // The seal sits in the middle of a wider transparent canvas. The box is sized to the seal;
        // the full, unscaled-ratio image is centred in it and only its transparent sides extend past.
        $sealBox = match ($size) {
            'sm' => 'size-10',
            'lg', 'xl' => 'size-16',
            'responsive' => 'size-12 sm:size-14',
            default => 'size-14',
        };
        [$firstLineSize, $secondLineSize] = match ($size) {
            'sm' => ['text-[0.7rem]', 'text-[1rem]'],
            'lg', 'xl' => ['text-[0.9375rem]', 'text-[1.5rem]'],
            'responsive' => ['text-[0.75rem] sm:text-[0.8125rem]', 'text-[1.125rem] sm:text-[1.3rem]'],
            default => ['text-[0.8125rem]', 'text-[1.3rem]'],
        };
    @endphp
    <span {{ $attributes->class(['inline-flex items-center gap-3', $isLight ? 'text-brand-cream' : 'text-foreground']) }}>
        <span class="{{ $sealBox }} relative shrink-0">
            <img
                src="{{ \App\Support\BrandAsset::url('UC Logo.png') }}"
                width="{{ $sealWidth }}"
                height="{{ $sealHeight }}"
                alt=""
                loading="lazy"
                decoding="async"
                draggable="false"
                class="pointer-events-none absolute left-1/2 top-0 h-full w-auto max-w-none -translate-x-1/2 object-contain select-none"
            >
        </span>
        <span class="flex flex-col gap-1 whitespace-nowrap leading-none">
            <span @class([$firstLineSize, 'font-semibold tracking-[0.06em]', 'text-brand-cream/80' => $isLight, 'text-muted' => ! $isLight])>UNIVERSITAS</span>
            <span class="{{ $secondLineSize }} font-bold tracking-tight">CIPUTRA</span>
        </span>
    </span>
@else
    @php
        [$width, $height] = \App\Support\BrandAsset::dimensions('UC Run Logo.PNG');

        $imageSize = match ($size) {
            'sm' => 'size-9',
            'lg' => 'size-16',
            'xl' => 'size-20',
            'responsive' => 'size-11 lg:size-14',
            default => 'size-12',
        };

        // Event name beside the logo: "UC Color Run" (primary) over "2027" (secondary).
        [$nameSize, $yearSize, $gap] = match ($size) {
            'sm' => ['text-[0.95rem]', 'text-[0.625rem]', 'gap-2'],
            'lg' => ['text-[1.45rem]', 'text-[0.8125rem]', 'gap-3'],
            'xl' => ['text-[1.75rem]', 'text-[0.9375rem]', 'gap-3.5'],
            'responsive' => [
                'text-[1rem] min-[25rem]:text-[1.125rem] lg:text-[1.375rem]',
                'text-[0.6875rem] lg:text-[0.8125rem]',
                'gap-2 lg:gap-3',
            ],
            default => ['text-[1.125rem]', 'text-[0.6875rem]', 'gap-2.5'],
        };

        $showsName = $variant !== 'mark';
    @endphp

    @if ($showsName)
        <span {{ $attributes->class(['inline-flex items-center', $gap]) }}>
            <img
                src="{{ \App\Support\BrandAsset::url('UC Run Logo.PNG') }}"
                width="{{ $width }}"
                height="{{ $height }}"
                alt=""
                decoding="async"
                draggable="false"
                class="{{ $imageSize }} shrink-0 object-contain select-none"
            >
            <span class="flex flex-col gap-0.5 whitespace-nowrap leading-none">
                <span @class([$nameSize, 'font-display', 'text-brand-cream' => $isLight, 'text-primary' => ! $isLight])>UC Color Run</span>
                <span @class([$yearSize, 'font-bold tracking-[0.32em]', 'text-brand-yellow' => $isLight, 'text-secondary-strong' => ! $isLight])>2027</span>
            </span>
        </span>
    @else
        <img
            src="{{ \App\Support\BrandAsset::url('UC Run Logo.PNG') }}"
            width="{{ $width }}"
            height="{{ $height }}"
            alt="{{ $alt }}"
            decoding="async"
            draggable="false"
            {{ $attributes->class([$imageSize, 'shrink-0 object-contain select-none']) }}
        >
    @endif
@endif
