<x-layouts.admin title="Registration Management" subtitle="Detail registrasi peserta.">
    <div class="mx-auto max-w-2xl overflow-hidden rounded-3xl border border-border bg-surface shadow-card">
        @fragment('details')
            <x-admin.detail-panel title="Registration Details" :subtitle="$registration->code" :close-href="route('admin.registrations.index')">
                <div class="flex flex-col gap-6">
                    <x-admin.detail-section title="1. Personal Information">
                        <x-admin.detail-item label="Full Nama (Nama Pembayar)">{{ $registration->full_name }}</x-admin.detail-item>
                        <x-admin.detail-item label="Email Address">{{ $registration->email }}</x-admin.detail-item>
                        <x-admin.detail-item label="Phone Number">{{ $registration->phone }}</x-admin.detail-item>
                        <x-admin.detail-item label="Date of Birth">{{ $registration->birth_date->translatedFormat('j F Y') }}</x-admin.detail-item>
                        <x-admin.detail-item label="Gender">{{ $registration->gender->label() }}</x-admin.detail-item>
                        {{-- Registrations made before the KTP photo field typed a KTP/Passport number instead. --}}
                        @if (filled($registration->identity_number))
                            <x-admin.detail-item label="KTP/Passport">{{ $registration->identity_number }}</x-admin.detail-item>
                        @endif
                        {{-- The payer's KTP, or the family's Kartu Keluarga for a Family Bundle. --}}
                        @php($identityDocument = $registration->identityDocument())
                        <x-admin.detail-item :label="$identityDocument->label()" class="min-[26rem]:col-span-2">
                            @if ($registration->hasIdentityPhoto())
                                @php($identityPhotoUrl = route('admin.registrations.identity-photo', $registration))
                                <a href="{{ $identityPhotoUrl }}" target="_blank" rel="noopener" class="group mt-1 block max-w-xs overflow-hidden rounded-xl border border-border bg-background">
                                    <img src="{{ $identityPhotoUrl }}" alt="{{ $identityDocument->label() }} {{ $registration->code }}" loading="lazy" class="aspect-3/2 w-full bg-foreground/5 object-contain">
                                    <span class="flex items-center gap-1.5 border-t border-border bg-surface px-3 py-2 text-body-sm font-semibold text-primary group-hover:underline">
                                        <x-icon name="eye" class="size-4" />
                                        Lihat ukuran penuh
                                    </span>
                                </a>
                            @else
                                <span class="font-normal text-muted">Belum diunggah</span>
                            @endif
                        </x-admin.detail-item>
                    </x-admin.detail-section>

                    <x-admin.detail-section title="2. Race Information">
                        <x-admin.detail-item label="Race Category">{{ $registration->category->label() }}</x-admin.detail-item>
                        <x-admin.detail-item label="Registration Type">{{ $registration->type->label() }}</x-admin.detail-item>
                        <x-admin.detail-item label="Price Type">{{ $registration->fee_period?->label() ?? 'Bundle' }}</x-admin.detail-item>
                        <x-admin.detail-item label="Package">{{ $registration->packageName() }}</x-admin.detail-item>
                        <x-admin.detail-item label="T-shirt Size">{{ $registration->tshirt_size->value }}</x-admin.detail-item>
                        <x-admin.detail-item label="Emergency Contact Name">{{ $registration->emergency_contact_name }}</x-admin.detail-item>
                        <x-admin.detail-item label="Emergency Contact Phone">{{ $registration->emergency_contact_phone }}</x-admin.detail-item>
                        <x-admin.detail-item label="Riwayat Penyakit" class="min-[26rem]:col-span-2">
                            <x-admin.medical-text :value="$registration->medical_history" />
                        </x-admin.detail-item>
                        <x-admin.detail-item label="Alergi Obat" class="min-[26rem]:col-span-2">
                            <x-admin.medical-text :value="$registration->drug_allergies" />
                        </x-admin.detail-item>
                    </x-admin.detail-section>

                    <x-admin.detail-section title="3. Address Information">
                        <x-admin.detail-item label="Province">{{ $registration->province }}</x-admin.detail-item>
                        <x-admin.detail-item label="City">{{ $registration->city }}</x-admin.detail-item>
                        <x-admin.detail-item label="Full Address" class="min-[26rem]:col-span-2">{{ $registration->address }}</x-admin.detail-item>
                    </x-admin.detail-section>

                    <x-admin.detail-section title="4. Registration Information">
                        <x-admin.detail-item label="Registration Date">{{ $registration->created_at->translatedFormat('j M Y') }}</x-admin.detail-item>
                        <x-admin.detail-item label="Amount">{{ \App\Support\Rupiah::format($registration->amount) }}</x-admin.detail-item>
                        <x-admin.detail-item label="Payment Date">{{ $registration->paid_at?->translatedFormat('j M Y') ?? '-' }}</x-admin.detail-item>
                        <x-admin.detail-item label="Payment Status">
                            <x-badge :tone="$registration->payment_status->tone()" class="mt-0.5">{{ $registration->payment_status->label() }}</x-badge>
                        </x-admin.detail-item>
                        <x-admin.detail-item label="Transaction ID">{{ $registration->transaction_id ?? '-' }}</x-admin.detail-item>
                        <x-admin.detail-item label="BIB Number">{{ $registration->bib_number ?? '-' }}</x-admin.detail-item>
                        <x-admin.detail-item label="BIB Collection" class="min-[26rem]:col-span-2">
                            @if ($registration->hasCollectedBib())
                                Sudah diambil · {{ $registration->bib_collected_at->translatedFormat('j M Y, H:i') }}@if ($registration->bibCollector) oleh {{ $registration->bibCollector->name }}@endif
                            @else
                                Belum diambil
                            @endif
                        </x-admin.detail-item>
                    </x-admin.detail-section>

                    @if ($registration->members->isNotEmpty())
                        <x-admin.detail-section title="Bundle Members">
                            @foreach ($registration->members as $member)
                                <x-admin.detail-item :label="'Peserta '.$member->position" class="min-[26rem]:col-span-2">
                                    {{ $member->full_name }} · {{ $member->tshirt_size->value }} · BIB {{ $member->bib_number ?? '-' }}
                                    <span class="mt-1 block text-body-sm font-normal">
                                        <span class="text-muted">Riwayat Penyakit:</span> <x-admin.medical-text :value="$member->medical_history" />
                                    </span>
                                    <span class="block text-body-sm font-normal">
                                        <span class="text-muted">Alergi Obat:</span> <x-admin.medical-text :value="$member->drug_allergies" />
                                    </span>
                                </x-admin.detail-item>
                            @endforeach
                        </x-admin.detail-section>
                    @endif

                    <section class="border-t border-border pt-6">
                        <h3 class="text-body font-bold">5. QR Code</h3>
                        <div class="mt-4 inline-block">
                            <x-racepack-qr :registration="$registration" size="size-40" />
                        </div>
                        @if ($registration->isPaid())
                            <p class="mt-3 text-caption text-muted">QR racepack yang dipindai di meja pengambilan BIB.</p>
                        @endif
                    </section>
                </div>
            </x-admin.detail-panel>
        @endfragment
    </div>
</x-layouts.admin>
