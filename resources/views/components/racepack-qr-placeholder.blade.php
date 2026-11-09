{{--
    Stands in for the BIB / racepack pickup QR code until that feature is built. It keeps the QR
    area's size and is clearly not a payment QRIS. Pass the size classes of the QR area it replaces.
--}}
@props(['size' => 'size-48 sm:size-56'])

<div {{ $attributes->class([$size, 'grid place-items-center rounded-xl border-2 border-dashed border-primary/25 bg-background p-4 text-center']) }} role="img" aria-label="QR pengambilan BIB segera hadir">
    <span class="flex flex-col items-center gap-2">
        <x-icon name="qr-code" class="size-10 text-primary/40" stroke-width="1.5" />
        <span class="text-caption font-bold uppercase tracking-[0.18em] text-primary">BIB Pickup QR</span>
        <span class="rounded-full bg-brand-yellow px-2.5 py-0.5 text-caption font-semibold text-primary">Coming Soon</span>
    </span>
</div>
