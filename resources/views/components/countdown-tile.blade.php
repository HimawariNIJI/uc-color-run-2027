{{--
    One countdown unit on the Design Division "Countdown Box" paper tile. The digits stay live text
    (updated by the countdown script), the tile is the image cropped to its painted area.
--}}
@props(['unit', 'value', 'label'])

<div {{ $attributes->class('relative isolate grid aspect-[2028/2099] min-w-0 place-items-center') }}>
    <div
        class="absolute inset-0 -z-10 bg-[length:202.1%_auto] bg-center bg-no-repeat drop-shadow-[0_10px_16px_rgb(34_17_79/0.35)]"
        style="background-image: url('{{ \App\Support\BrandAsset::url('Countdown Box.png') }}')"
        aria-hidden="true"
    ></div>
    <div class="flex flex-col items-center px-1">
        <span class="font-display text-[clamp(1.75rem,7vw,3.5rem)] leading-none text-primary" data-countdown-unit="{{ $unit }}">{{ sprintf('%02d', $value) }}</span>
        <span class="mt-1 text-caption font-semibold uppercase tracking-wider text-primary/75 sm:text-body-sm">{{ $label }}</span>
    </div>
</div>
