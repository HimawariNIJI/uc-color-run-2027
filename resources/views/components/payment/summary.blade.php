{{--
    Registration summary block. Optional "header" slot renders above the title (status banner);
    the default slot adds rows under the total (e.g. payment status).
--}}
@props(['registration', 'title' => 'Ringkasan Registrasi'])

<div {{ $attributes->class('rounded-2xl border border-border bg-background p-4 sm:p-5') }}>
    @isset($header)
        <div class="mb-4 border-b border-border pb-4">{{ $header }}</div>
    @endisset

    <p class="text-caption font-semibold uppercase tracking-wider text-muted">{{ $title }}</p>

    <dl class="mt-3 flex flex-col gap-2.5">
        <x-detail-row label="Full Nama (Nama Pembayar)">{{ $registration->full_name }}</x-detail-row>
        <x-detail-row label="Kategori race">{{ $registration->raceLabel() }}</x-detail-row>
        <x-detail-row label="Ukuran T-shirt">{{ $registration->tshirt_size->label() }}</x-detail-row>
    </dl>

    <dl class="mt-4 flex flex-col gap-2.5 border-t border-border pt-4">
        <x-detail-row label="Total pembayaran" emphasis>{{ \App\Support\Rupiah::format($registration->amount) }}</x-detail-row>
        {{ $slot }}
    </dl>
</div>
