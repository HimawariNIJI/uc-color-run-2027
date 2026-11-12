{{--
    An accepted racepack scan: the registration's shared details once, then every participant with
    their own BIB. Rendered in the admin modal right after a scan, from Recent QR Scanned, or as a page.
--}}
@php
    $registration = $scan->registration;
    $identityDocument = $registration->identityDocument();
    $participants = collect([[
        'position' => 1,
        'role' => $registration->members->isEmpty() ? null : 'Pembayar',
        'name' => $registration->full_name,
        'bib' => $registration->bib_number,
        'gender' => $registration->gender,
        'birth_date' => $registration->birth_date,
        'tshirt_size' => $registration->tshirt_size,
        'medical_history' => $registration->medical_history,
        'drug_allergies' => $registration->drug_allergies,
    ]])->concat($registration->members->map(fn (\App\Models\RegistrationMember $member): array => [
        'position' => $member->position,
        'role' => null,
        'name' => $member->full_name,
        'bib' => $member->bib_number,
        'gender' => $member->gender,
        'birth_date' => $member->birth_date,
        'tshirt_size' => $member->tshirt_size,
        'medical_history' => $member->medical_history,
        'drug_allergies' => $member->drug_allergies,
    ]));
@endphp

<x-layouts.admin title="QR Scanner" subtitle="Detail QR racepack yang dipindai.">
    <div class="mx-auto max-w-2xl overflow-hidden rounded-3xl border border-border bg-surface shadow-card">
        @fragment('details')
            <x-admin.detail-panel
                title="QR Racepack Valid"
                :subtitle="$registration->code.' · '.$participants->count().' peserta · dipindai '.$scan->scanned_at->translatedFormat('j M Y, H:i').($scan->admin ? ' oleh '.$scan->admin->name : '')"
                :close-href="route('admin.scanner.index')"
                data-scan-result
            >
                <div class="flex flex-col gap-6">
                    @if ($scan->bib_handed_over)
                        <x-alert tone="success" :title="$participants->count() > 1 ? 'Serahkan '.$participants->count().' BIB' : 'Serahkan BIB'">
                            BIB {{ $participants->pluck('bib')->implode(', ') }} diserahkan saat scan ini.
                        </x-alert>
                    @else
                        <x-alert tone="warning" title="BIB sudah diambil sebelumnya">
                            Diambil {{ $registration->bib_collected_at?->translatedFormat('j M Y, H:i') }}@if ($registration->bibCollector) oleh {{ $registration->bibCollector->name }}@endif. Periksa sebelum menyerahkan BIB lagi.
                        </x-alert>
                    @endif

                    <x-admin.detail-section title="1. Registration">
                        <x-admin.detail-item label="Registration ID">{{ $registration->code }}</x-admin.detail-item>
                        <x-admin.detail-item label="Package">{{ $registration->packageName() }}</x-admin.detail-item>
                        <x-admin.detail-item label="Race Distance">{{ $registration->category->label() }}</x-admin.detail-item>
                        <x-admin.detail-item label="Registration Type">{{ $registration->type->label() }}</x-admin.detail-item>
                        <x-admin.detail-item label="Price Type">{{ $registration->fee_period?->label() ?? 'Bundle' }}</x-admin.detail-item>
                        <x-admin.detail-item label="Participants">{{ $participants->count() }}</x-admin.detail-item>
                    </x-admin.detail-section>

                    <x-admin.detail-section title="2. Payment">
                        <x-admin.detail-item label="Payment Status">
                            <x-badge :tone="$registration->payment_status->tone()" class="mt-0.5">{{ $registration->payment_status->label() }}</x-badge>
                        </x-admin.detail-item>
                        <x-admin.detail-item label="Amount">{{ \App\Support\Rupiah::format($registration->amount) }}</x-admin.detail-item>
                        <x-admin.detail-item label="Paid At">{{ $registration->paid_at?->translatedFormat('j M Y, H:i') ?? '-' }}</x-admin.detail-item>
                        <x-admin.detail-item label="Transaction ID">{{ $registration->transaction_id ?? '-' }}</x-admin.detail-item>
                    </x-admin.detail-section>

                    <x-admin.detail-section title="3. Payer & Contact">
                        <x-admin.detail-item label="Full Nama (Nama Pembayar)">{{ $registration->full_name }}</x-admin.detail-item>
                        <x-admin.detail-item label="Email Address">{{ $registration->email }}</x-admin.detail-item>
                        <x-admin.detail-item label="Phone Number">{{ $registration->phone }}</x-admin.detail-item>
                        <x-admin.detail-item label="Emergency Contact">{{ $registration->emergency_contact_name }} · {{ $registration->emergency_contact_phone }}</x-admin.detail-item>
                        <x-admin.detail-item :label="$identityDocument->label()" class="min-[26rem]:col-span-2">
                            @if ($registration->hasIdentityPhoto())
                                <a href="{{ route('admin.registrations.identity-photo', $registration) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold text-primary hover:underline">
                                    <x-icon name="eye" class="size-4" />
                                    Lihat {{ $identityDocument->noun() }}
                                </a>
                            @else
                                <span class="font-normal text-muted">Belum diunggah</span>
                            @endif
                        </x-admin.detail-item>
                    </x-admin.detail-section>

                    <section class="border-t border-border pt-6">
                        <h3 class="text-body font-bold">4. Participants ({{ $participants->count() }})</h3>
                        <ul class="mt-4 flex flex-col gap-3">
                            @foreach ($participants as $participant)
                                <li class="rounded-2xl border border-border p-4" data-scan-participant>
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                        <p class="font-semibold">
                                            Peserta {{ $participant['position'] }} · {{ $participant['name'] }}
                                            @if ($participant['role'])
                                                <span class="font-normal text-muted">({{ $participant['role'] }})</span>
                                            @endif
                                        </p>
                                        <p class="text-body font-bold tabular-nums text-primary">BIB {{ $participant['bib'] }}</p>
                                    </div>
                                    <dl class="mt-3 grid gap-x-6 gap-y-3 min-[26rem]:grid-cols-3">
                                        <x-admin.detail-item label="Gender">{{ $participant['gender']->label() }}</x-admin.detail-item>
                                        <x-admin.detail-item label="Date of Birth">{{ $participant['birth_date']->translatedFormat('j M Y') }}</x-admin.detail-item>
                                        <x-admin.detail-item label="T-shirt Size">{{ $participant['tshirt_size']->value }}</x-admin.detail-item>
                                        <x-admin.detail-item label="Riwayat Penyakit" class="min-[26rem]:col-span-3">
                                            <x-admin.medical-text :value="$participant['medical_history']" />
                                        </x-admin.detail-item>
                                        <x-admin.detail-item label="Alergi Obat" class="min-[26rem]:col-span-3">
                                            <x-admin.medical-text :value="$participant['drug_allergies']" />
                                        </x-admin.detail-item>
                                    </dl>
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    <div class="flex flex-col gap-2 border-t border-border pt-6 min-[26rem]:flex-row min-[26rem]:justify-end">
                        <x-button :href="route('admin.registrations.show', $registration)" variant="secondary" pill>Registration Details</x-button>
                        <x-button :href="route('admin.scanner.index')" pill data-admin-modal-close>Scan QR berikutnya</x-button>
                    </div>
                </div>
            </x-admin.detail-panel>
        @endfragment
    </div>
</x-layouts.admin>
