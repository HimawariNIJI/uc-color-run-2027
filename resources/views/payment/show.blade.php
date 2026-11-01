<x-layouts.app title="Pembayaran">
    <x-payment.card>
        <div>
            <h1 class="text-body-lg font-bold">Selesaikan Pembayaran</h1>
            <p class="mt-0.5 text-body-sm text-muted">Satu langkah lagi untuk mengamankan slot registrasimu!</p>
        </div>

        <x-payment.summary :registration="$registration" />

        <section class="rounded-2xl border border-border p-4 text-center sm:p-5">
            <h2 class="flex items-center justify-center gap-2 text-body font-semibold">
                <x-icon name="qr-code" class="size-5 text-primary" />
                Bayar dengan QRIS
            </h2>

            {{-- Placeholder until the payment gateway supplies the QRIS image. --}}
            <div class="mx-auto mt-4 grid size-48 place-items-center rounded-2xl border-2 border-dashed border-border bg-background sm:size-52" role="img" aria-label="Kode QRIS">
                <span class="flex flex-col items-center gap-2 text-muted">
                    <x-icon name="qr-code" class="size-14" stroke-width="1.5" />
                    <span class="text-body-sm font-bold tracking-[0.3em] text-foreground">QRIS</span>
                </span>
            </div>

            <p class="mx-auto mt-4 max-w-xs text-body-sm text-muted">Scan QR menggunakan aplikasi mobile banking atau e-wallet yang mendukung QRIS.</p>

            <x-alert tone="warning" title="Selesaikan sebelum kedaluwarsa" class="mt-4 text-left">
                Batas waktu: {{ $registration->payment_expires_at->translatedFormat('j F Y, H.i') }} WIB
                <span class="whitespace-nowrap" data-countdown="{{ $registration->payment_expires_at->toIso8601String() }}">
                    (sisa <span class="tabular-nums" data-countdown-unit="minutes">{{ sprintf('%02d', intdiv($secondsLeft, 60)) }}</span>:<span class="tabular-nums" data-countdown-unit="seconds">{{ sprintf('%02d', $secondsLeft % 60) }}</span>)
                </span>
            </x-alert>
        </section>

        <section>
            <h2 class="text-body-sm font-semibold">Petunjuk pembayaran</h2>
            <ol class="mt-3 flex flex-col gap-2.5">
                @foreach (['Buka aplikasi mobile banking atau e-wallet.', 'Pilih menu Scan QRIS, lalu pindai kode di atas.', 'Pastikan nominal sesuai, lalu konfirmasi pembayaran.'] as $step)
                    <li class="flex items-start gap-3 text-body-sm">
                        <span class="grid size-6 shrink-0 place-items-center rounded-full bg-primary/10 text-caption font-bold text-primary">{{ $loop->iteration }}</span>
                        <span class="pt-0.5">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
        </section>

        <form method="POST" action="{{ route('payment.check') }}" data-submit-guard>
            @csrf
            <x-button type="submit" size="lg" pill block data-busy-label="Mengecek status…">Cek Status Pembayaran</x-button>
        </form>

        @if ($canSimulatePayment)
            <div class="rounded-2xl border border-dashed border-warning/60 bg-warning/5 p-4">
                <p class="flex items-center gap-2 text-caption font-semibold uppercase tracking-wider text-warning-strong">
                    <x-icon name="info" class="size-4" />
                    Simulasi pembayaran (local only)
                </p>
                <p class="mt-1 text-caption text-muted">Payment gateway belum terhubung. Gunakan tombol ini untuk menguji alur pembayaran.</p>
                <div class="mt-3 grid gap-2 min-[25rem]:grid-cols-2">
                    <form method="POST" action="{{ route('payment.simulate', 'paid') }}">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm" block>Tandai Lunas</x-button>
                    </form>
                    <form method="POST" action="{{ route('payment.simulate', 'failed') }}">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm" block>Tandai Gagal</x-button>
                    </form>
                </div>
            </div>
        @endif
    </x-payment.card>
</x-layouts.app>
