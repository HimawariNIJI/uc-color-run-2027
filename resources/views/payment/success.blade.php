<x-layouts.app title="Pembayaran Berhasil">
    <x-payment.card :help="false">
        <x-payment.summary :registration="$registration" title="Ringkasan Pembayaran">
            <x-slot:header>
                <div class="flex items-start gap-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-success/12 text-success-strong">
                        <x-icon name="circle-check" class="size-5" />
                    </span>
                    <div>
                        <h1 class="text-body font-bold">Pembayaran Berhasil!</h1>
                        <p class="text-caption text-muted">Pembayaran kamu telah dikonfirmasi.</p>
                    </div>
                </div>
            </x-slot:header>

            <x-detail-row label="Status">
                <x-badge tone="success">{{ $registration->payment_status->settlementLabel() }}</x-badge>
            </x-detail-row>
            <x-detail-row label="Kode registrasi">{{ $registration->code }}</x-detail-row>
            <x-detail-row label="Nomor BIB"><span class="font-semibold tabular-nums">{{ $registration->bib_number }}</span></x-detail-row>
            @foreach ($registration->members as $member)
                <x-detail-row :label="'BIB '.$member->full_name"><span class="font-semibold tabular-nums">{{ $member->bib_number }}</span></x-detail-row>
            @endforeach
        </x-payment.summary>

        <div class="flex flex-col items-center rounded-2xl border border-border px-5 py-6 text-center">
            <p class="text-caption font-semibold uppercase tracking-wider text-muted">QR Racepack</p>
            <x-racepack-qr :registration="$registration" size="size-44" class="mt-3" />
            <p class="mt-3 max-w-xs text-caption text-muted">Tunjukkan QR ini kepada panitia saat pengambilan race pack. QR yang sama juga ada di halaman My BIB.</p>
        </div>

        <div class="rounded-2xl border border-border px-5 py-6">
            <x-state-message icon="badge-check" title="Registrasi Terkonfirmasi" tone="success">
                Terima kasih telah mendaftar UC Color Run. Sampai jumpa di garis start!
            </x-state-message>
        </div>

        <x-button :href="route('my-registration.show')" size="lg" pill block>Lihat Detail Registrasi</x-button>
    </x-payment.card>
</x-layouts.app>
