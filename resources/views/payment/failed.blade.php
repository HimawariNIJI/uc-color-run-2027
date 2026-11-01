<x-layouts.app title="Pembayaran Gagal">
    <x-payment.card>
        <x-payment.summary :registration="$registration" title="Ringkasan Pembayaran">
            <x-slot:header>
                <div class="flex items-start gap-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-error/10 text-error">
                        <x-icon name="circle-x" class="size-5" />
                    </span>
                    <div>
                        <h1 class="text-body font-bold">Pembayaran Gagal</h1>
                        <p class="text-caption text-muted">Pembayaran belum berhasil dikonfirmasi.</p>
                    </div>
                </div>
            </x-slot:header>

            <x-detail-row label="Status">
                <x-badge tone="error">{{ $registration->payment_status->settlementLabel() }}</x-badge>
            </x-detail-row>
        </x-payment.summary>

        <div class="rounded-2xl border border-border px-5 py-6">
            <x-state-message icon="circle-x" title="Transaksi Belum Berhasil" tone="error">
                Pembayaran kamu belum diterima. Silakan coba kembali atau periksa status transaksi sebelum melakukan pembayaran ulang.
            </x-state-message>
        </div>

        <div class="flex flex-col gap-3">
            <form method="POST" action="{{ route('payment.retry') }}" data-submit-guard>
                @csrf
                <x-button type="submit" size="lg" pill block>Coba Bayar Lagi</x-button>
            </form>
            <form method="POST" action="{{ route('payment.check') }}" data-submit-guard>
                @csrf
                <x-button type="submit" variant="secondary" size="lg" pill block data-busy-label="Mengecek status…">Cek Status Pembayaran</x-button>
            </form>
        </div>
    </x-payment.card>
</x-layouts.app>
