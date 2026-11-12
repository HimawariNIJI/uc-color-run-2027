{{--
    The racepack QR participants show at BIB collection: one QR holding every participant's ID
    (see App\Support\RacepackQr), with the BIB numbers under it. It is only active once the payment
    is confirmed. Until then, or when the QR cannot be rendered, a clear status is shown instead.

    size: size classes of the QR area.
--}}
@props(['registration', 'size' => 'size-48 sm:size-56'])

@php
    $svg = \App\Support\RacepackQr::svgFor($registration);
    $payload = $svg !== null ? \App\Support\RacepackQr::payloadFor($registration) : null;
@endphp

<div {{ $attributes->class('flex flex-col items-center') }}>
    @if ($svg !== null)
        <div class="rounded-2xl border border-border bg-white p-3 shadow-card">
            <div class="{{ $size }} [&_svg]:size-full" role="img" aria-label="QR racepack {{ $payload }}" data-racepack-qr="{{ $payload }}">
                {!! $svg !!}
            </div>
        </div>
        <ul class="mt-3 flex max-w-xs flex-wrap justify-center gap-1.5" aria-label="Nomor BIB di dalam QR">
            @foreach ($registration->participantBibNumbers() as $bibNumber)
                <li class="rounded-full bg-primary/8 px-2.5 py-0.5 text-caption font-semibold tabular-nums text-primary">BIB {{ $bibNumber }}</li>
            @endforeach
        </ul>
    @elseif ($registration->isPaid())
        <div class="{{ $size }} flex flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-error/40 bg-error/5 p-4 text-center" role="alert">
            <x-icon name="circle-alert" class="size-7 text-error" />
            <p class="text-caption font-semibold text-error">QR racepack belum dapat dibuat.</p>
            <p class="text-caption text-muted">
                <a href="{{ url()->current() }}" class="font-semibold text-primary underline-offset-2 hover:underline">Muat ulang</a> atau hubungi panitia.
            </p>
        </div>
    @else
        <div class="{{ $size }} flex flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-border bg-background p-4 text-center">
            <x-icon name="qr-code" class="size-7 text-muted" />
            <p class="text-caption text-muted">QR tersedia setelah pembayaran lunas.</p>
        </div>
    @endif
</div>
