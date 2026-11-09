<x-layouts.app title="Pembayaran">
    <x-payment.card>
        <div>
            <h1 class="text-body-lg font-bold">Selesaikan Pembayaran</h1>
            <p class="mt-0.5 text-body-sm text-muted">Satu langkah lagi untuk mengamankan slot registrasimu!</p>
        </div>

        <x-payment.summary :registration="$registration">
            <x-detail-row label="Kode registrasi"><span class="font-semibold">{{ $registration->code }}</span></x-detail-row>
            <x-detail-row label="Status">
                <x-badge tone="warning">{{ \App\Enums\TransactionStatus::Pending->label() }}</x-badge>
            </x-detail-row>
        </x-payment.summary>

        {{-- Polls the server, which asks Midtrans; the page moves on once the payment is confirmed. --}}
        <section class="rounded-2xl border border-border p-4 text-center sm:p-5" data-payment-status="{{ route('payment.status') }}">
            <h2 class="flex items-center justify-center gap-2 text-body font-semibold">
                <x-icon name="qr-code" class="size-5 text-primary" />
                Bayar dengan QRIS
            </h2>

            @if ($qrSvg)
                {{-- The QRIS exactly as Midtrans created it for this registration. --}}
                <div class="mx-auto mt-4 w-fit rounded-2xl border border-border bg-white p-3 shadow-card">
                    <div class="size-52 sm:size-60 [&>svg]:size-full" role="img" aria-label="Kode QRIS pembayaran {{ $registration->code }}">
                        {!! $qrSvg !!}
                    </div>
                </div>
                <p class="mt-3 text-caption text-muted">Order ID: <span class="font-semibold text-foreground">{{ $payment->order_id }}</span></p>
            @else
                <div class="mx-auto mt-4 grid size-48 place-items-center rounded-2xl border-2 border-dashed border-border bg-background sm:size-52" role="img" aria-label="Kode QRIS belum tersedia">
                    <span class="flex flex-col items-center gap-2 text-muted">
                        <x-icon name="qr-code" class="size-14" stroke-width="1.5" />
                        <span class="text-body-sm font-bold tracking-[0.3em] text-foreground">QRIS</span>
                    </span>
                </div>
                @if ($canCreateQris)
                    <p class="mx-auto mt-3 max-w-xs text-caption text-muted">Kode QRIS belum dibuat untuk registrasi ini.</p>
                    <form method="POST" action="{{ route('payment.retry') }}" class="mx-auto mt-3 max-w-xs" data-submit-guard>
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm" block data-busy-label="Membuat kode QRIS…">Buat Kode QRIS</x-button>
                    </form>
                @else
                    <p class="mx-auto mt-3 max-w-xs text-caption text-muted">Kode QRIS belum tersedia karena payment gateway belum dikonfigurasi.</p>
                @endif
            @endif

            <p class="mx-auto mt-4 max-w-xs text-body-sm text-muted">Scan QR menggunakan aplikasi mobile banking atau e-wallet yang mendukung QRIS.</p>

            <x-alert tone="warning" title="Selesaikan sebelum kedaluwarsa" class="mt-4 text-left">
                Batas waktu: {{ $expiresAt->translatedFormat('j F Y, H.i') }} WIB
                <span class="whitespace-nowrap" data-countdown="{{ $expiresAt->toIso8601String() }}">
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
                <p class="mt-1 text-caption text-muted">Kunci Midtrans belum diisi. Gunakan tombol ini untuk menguji alur pembayaran; tombol ini hilang setelah MIDTRANS_SERVER_KEY diisi.</p>
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
