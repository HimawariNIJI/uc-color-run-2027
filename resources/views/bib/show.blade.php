<x-layouts.app title="My BIB">
    <div class="page-container page-section">
        <x-panel>
            <x-panel-heading title="My BIB" description="Nomor BIB dan QR racepack kamu akan tersedia di sini setelah registrasi dan pembayaran terkonfirmasi." />

            @if ($hasBib)
                <div class="mt-8 flex flex-col items-center text-center md:mt-10">
                    <p class="text-caption font-semibold uppercase tracking-[0.2em] text-muted">Your BIB Number</p>
                    <p class="mt-2 text-h1 tracking-tight text-primary tabular-nums">{{ $registration->bib_number }}</p>
                    <p class="mt-2 text-body-sm text-muted">{{ $registration->full_name }} &middot; {{ $registration->raceLabel() }}</p>

                    @if ($registration->members->isNotEmpty())
                        <ul class="mt-4 flex flex-col gap-1 text-body-sm">
                            @foreach ($registration->members as $member)
                                <li><span class="font-semibold tabular-nums text-primary">{{ $member->bib_number }}</span> &middot; {{ $member->full_name }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <p class="mt-8 text-caption font-semibold uppercase tracking-[0.2em] text-muted">Racepack QR-Code</p>
                    <div class="mt-3 rounded-2xl border border-border bg-white p-3 shadow-card">
                        <x-racepack-qr-placeholder />
                    </div>
                    <p class="mt-4 max-w-sm text-body-sm text-muted">QR pengambilan race pack akan tersedia di sini. Kode registrasi: <span class="font-semibold text-foreground">{{ $registration->code }}</span></p>
                </div>
            @else
                <x-state-message class="py-8 md:py-12" icon="lock" title="Your BIB Is Not Available Yet" tone="neutral">
                    You need to complete your registration and payment before your race BIB can be issued.
                </x-state-message>

                <div class="flex justify-center">
                    @if ($registration)
                        <x-button :href="route('my-registration.show')" variant="secondary">Cek Status Registrasi</x-button>
                    @else
                        <x-button :href="route('registration.type')">Register Now</x-button>
                    @endif
                </div>
            @endif
        </x-panel>
    </div>
</x-layouts.app>
