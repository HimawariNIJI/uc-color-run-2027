<x-layouts.app title="Sign In">
    <div class="page-container page-section">
        <x-panel width="card">
            <x-panel-heading title="Sign In" :divider="false" description="Masuk dengan akun Google untuk mendaftar, menyelesaikan pembayaran, dan melihat nomor BIB-mu." />

            <div class="mt-6 flex flex-col gap-4">
                @if ($isGoogleConfigured)
                    <x-google-sign-in-button />
                @else
                    <x-alert tone="warning" title="Login Google belum tersedia">
                        @if ($showsSetupHint)
                            Isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di file .env untuk mengaktifkan login Google.
                        @else
                            Silakan coba beberapa saat lagi atau hubungi panitia.
                        @endif
                    </x-alert>
                @endif

                <p class="text-center text-caption text-muted">
                    Dengan masuk, kamu menyetujui
                    <a href="{{ route('race.regulation') }}" class="font-semibold text-primary hover:underline">Race Regulation</a>
                    dan
                    <a href="{{ route('privacy') }}" class="font-semibold text-primary hover:underline">Privacy Policy</a>.
                </p>
            </div>
        </x-panel>
    </div>
</x-layouts.app>
