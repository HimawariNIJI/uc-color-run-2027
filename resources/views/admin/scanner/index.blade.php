<x-layouts.admin title="QR Scanner" subtitle="Verifikasi QR racepack peserta dan serahkan BIB.">
    <x-admin.card title="QR Scanner" description="Scan the QR Code to collect your BIB" divided>
        <div class="flex flex-col items-center" data-qr-scanner data-scan-url="{{ route('admin.scanner.store') }}" data-recent-limit="{{ $recentScanLimit }}">
            <div class="relative aspect-square w-full max-w-sm overflow-hidden rounded-3xl bg-primary/6">
                <video class="size-full bg-foreground object-cover" playsinline muted hidden data-scanner-video></video>

                <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 rounded-3xl border-2 border-dashed border-primary/25 px-6 text-center" data-scanner-idle>
                    <span class="grid size-16 place-items-center rounded-2xl bg-surface text-primary shadow-card">
                        <x-icon name="scan-qr-code" class="size-8" />
                    </span>
                    <p class="text-h4" data-scanner-idle-title>QR Scanner OFF</p>
                    <p class="max-w-60 text-body-sm text-muted" data-scanner-idle-text>Tekan Start Camera lalu arahkan kamera ke QR racepack peserta.</p>
                </div>

                <div class="pointer-events-none absolute inset-0" hidden data-scanner-live>
                    <span class="absolute left-1/2 top-4 inline-flex -translate-x-1/2 items-center gap-2 rounded-full bg-foreground/70 px-3 py-1.5 text-caption font-semibold text-white">
                        <span class="size-2 animate-pulse rounded-full bg-success"></span>
                        QR Scanner ON
                    </span>
                    {{-- Aiming frame --}}
                    <span class="absolute inset-[18%] rounded-2xl shadow-[0_0_0_9999px_rgb(23_23_23/0.35)]"></span>
                    <span class="absolute left-[18%] top-[18%] size-8 rounded-tl-2xl border-l-4 border-t-4 border-white"></span>
                    <span class="absolute right-[18%] top-[18%] size-8 rounded-tr-2xl border-r-4 border-t-4 border-white"></span>
                    <span class="absolute bottom-[18%] left-[18%] size-8 rounded-bl-2xl border-b-4 border-l-4 border-white"></span>
                    <span class="absolute bottom-[18%] right-[18%] size-8 rounded-br-2xl border-b-4 border-r-4 border-white"></span>
                </div>
            </div>

            <div class="mt-5 flex justify-center">
                <x-button pill data-scanner-start>
                    <x-icon name="camera" class="size-4.5" />
                    Start Camera
                </x-button>
                <x-button variant="secondary" pill hidden data-scanner-stop>
                    <x-icon name="camera-off" class="size-4.5" />
                    Stop Camera
                </x-button>
            </div>

            <div class="mt-5 w-full max-w-xl empty:hidden" aria-live="polite" data-scanner-result></div>

            <form method="POST" action="{{ route('admin.scanner.store') }}" class="mt-6 w-full max-w-xl rounded-2xl border border-border bg-background p-4 sm:p-5" data-scanner-manual>
                @csrf
                <label for="code" class="flex items-center gap-2 text-label">
                    <x-icon name="keyboard" class="size-4.5 text-muted" />
                    Kamera tidak tersedia? Masukkan Registration ID
                </label>
                <div class="mt-3 flex flex-col gap-2 min-[26rem]:flex-row">
                    <input
                        type="text"
                        name="code"
                        id="code"
                        placeholder="UCR-2027-XXXXX"
                        maxlength="30"
                        autocomplete="off"
                        autocapitalize="characters"
                        spellcheck="false"
                        required
                        class="form-control h-11 min-w-0 rounded-full px-5 uppercase placeholder:normal-case min-[26rem]:flex-1"
                    >
                    <x-button type="submit" pill class="min-h-11">Verifikasi</x-button>
                </div>
            </form>

            @foreach (['success', 'warning', 'error'] as $tone)
                <template data-scanner-template="{{ $tone }}">
                    <x-alert :tone="$tone" dismissible><span data-scanner-message></span></x-alert>
                </template>
            @endforeach
        </div>

        <h3 class="mt-10 text-body-lg font-semibold">Recent QR Scanned</h3>

        <x-admin.table class="mt-4" label="QR yang baru dipindai" min-width="min-w-[36rem]" body-id="recent-scans">
            <x-slot:head>
                <th scope="col">Registration ID</th>
                <th scope="col">Participant</th>
                <th scope="col">Category</th>
                <th scope="col">BIB Number</th>
            </x-slot:head>

            @foreach ($recentScans as $registration)
                @include('admin.scanner.partials.scan-row')
            @endforeach

            <x-admin.empty-row colspan="4" icon="scan-qr-code" title="Belum ada QR yang dipindai." :hidden="$recentScans->isNotEmpty()" data-scanner-empty>
                Peserta yang sudah mengambil BIB akan muncul di sini.
            </x-admin.empty-row>
        </x-admin.table>
    </x-admin.card>
</x-layouts.admin>
