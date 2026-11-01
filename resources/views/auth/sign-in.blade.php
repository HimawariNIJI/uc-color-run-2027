<x-layouts.app title="Sign In">
    <div class="page-container page-section">
        <x-panel width="card">
            <x-panel-heading title="Sign In" :divider="false" description="Masuk dengan akun Google untuk mendaftar, menyelesaikan pembayaran, dan melihat nomor BIB-mu." />

            <div class="mt-6 flex flex-col gap-4">
                @if ($isGoogleConfigured)
                    <x-button :href="route('auth.google')" variant="secondary" size="lg" block>
                        <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.46a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.58-5.17 3.58-8.81z" />
                            <path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.9l-3.88-3.02c-1.07.72-2.45 1.15-4.06 1.15-3.12 0-5.77-2.11-6.71-4.95H1.28v3.11A12 12 0 0 0 12 24z" />
                            <path fill="#FBBC05" d="M5.29 14.28A7.2 7.2 0 0 1 4.91 12c0-.79.14-1.56.38-2.28V6.61H1.28A12 12 0 0 0 0 12c0 1.94.46 3.77 1.28 5.39l4.01-3.11z" />
                            <path fill="#EA4335" d="M12 4.77c1.76 0 3.34.61 4.59 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.28 6.61l4.01 3.11C6.23 6.88 8.88 4.77 12 4.77z" />
                        </svg>
                        Sign in with Google
                    </x-button>
                @else
                    <x-alert tone="warning" title="Login Google belum tersedia">
                        @if ($canSignInLocally)
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

                @if ($canSignInLocally)
                    <div class="mt-2 rounded-2xl border border-dashed border-warning/60 bg-warning/5 p-4 sm:p-5">
                        <p class="flex items-center gap-2 text-caption font-semibold uppercase tracking-wider text-warning-strong">
                            <x-icon name="info" class="size-4" />
                            Local development only
                        </p>
                        <p class="mt-1 text-caption text-muted">Masuk tanpa Google untuk menguji halaman peserta. Tidak tersedia di production.</p>

                        <form method="POST" action="{{ route('login.local') }}" class="mt-4 flex flex-col gap-3" novalidate data-submit-guard>
                            @csrf
                            <x-form.field label="Nama" for="name">
                                <x-form.input name="name" value="Peserta Demo" autocomplete="name" required />
                            </x-form.field>
                            <x-form.field label="Email" for="email">
                                <x-form.input type="email" name="email" value="peserta@example.com" autocomplete="email" required />
                            </x-form.field>
                            <x-button type="submit" variant="secondary" block>Masuk sebagai peserta (dev)</x-button>
                        </form>
                    </div>
                @endif
            </div>
        </x-panel>
    </div>
</x-layouts.app>
