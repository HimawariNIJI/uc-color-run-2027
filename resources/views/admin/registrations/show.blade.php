<x-layouts.admin title="Registration Management" subtitle="Detail registrasi peserta.">
    <div class="mx-auto max-w-2xl overflow-hidden rounded-3xl border border-border bg-surface shadow-card">
        @fragment('details')
            <x-admin.detail-panel title="Registration Details" :subtitle="$registration->code" :close-href="route('admin.registrations.index')">
                <div class="flex flex-col gap-6">
                    <x-admin.detail-section title="1. Personal Information">
                        <x-admin.detail-item label="Full Name">{{ $registration->full_name }}</x-admin.detail-item>
                        <x-admin.detail-item label="Email Address">{{ $registration->email }}</x-admin.detail-item>
                        <x-admin.detail-item label="Phone Number">{{ $registration->phone }}</x-admin.detail-item>
                        <x-admin.detail-item label="Date of Birth">{{ $registration->birth_date->translatedFormat('j F Y') }}</x-admin.detail-item>
                        <x-admin.detail-item label="Gender">{{ $registration->gender->label() }}</x-admin.detail-item>
                        <x-admin.detail-item label="KTP/Passport">{{ $registration->identity_number }}</x-admin.detail-item>
                    </x-admin.detail-section>

                    <x-admin.detail-section title="2. Race Information">
                        <x-admin.detail-item label="Race Category">{{ $registration->category->label() }}</x-admin.detail-item>
                        <x-admin.detail-item label="Registration Type">{{ $registration->type->label() }}</x-admin.detail-item>
                        <x-admin.detail-item label="Price Type">{{ $registration->fee_period->label() }}</x-admin.detail-item>
                        <x-admin.detail-item label="T-shirt Size">{{ $registration->tshirt_size->value }}</x-admin.detail-item>
                        <x-admin.detail-item label="Emergency Contact Name">{{ $registration->emergency_contact_name }}</x-admin.detail-item>
                        <x-admin.detail-item label="Emergency Contact Phone">{{ $registration->emergency_contact_phone }}</x-admin.detail-item>
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

                    <section class="border-t border-border pt-6">
                        <h3 class="text-body font-bold">5. QR Code</h3>
                        @if ($racepackQrSvg)
                            <div class="mt-4 inline-block rounded-2xl border border-border bg-white p-3">
                                <div class="size-40 [&>svg]:size-full" role="img" aria-label="QR code racepack {{ $registration->code }}">
                                    {!! $racepackQrSvg !!}
                                </div>
                            </div>
                        @else
                            <div class="mt-4 flex size-40 flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-border bg-background p-4 text-center">
                                <x-icon name="qr-code" class="size-7 text-muted" />
                                <p class="text-caption text-muted">QR tersedia setelah pembayaran lunas.</p>
                            </div>
                        @endif
                    </section>
                </div>
            </x-admin.detail-panel>
        @endfragment
    </div>
</x-layouts.admin>
