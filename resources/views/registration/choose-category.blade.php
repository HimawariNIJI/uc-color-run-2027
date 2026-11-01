@use('App\Support\NumberFormat')
@use('App\Support\Rupiah')

<x-layouts.app title="Pilih Jarak Lari" description="Pilih kategori jarak lari UC Color Run 2027: 3 KM, 5 KM, atau 10 KM.">
    <div class="page-container page-section">
        <x-panel>
            <x-registration-steps :current="3" />
            <h1 class="mt-6 text-center text-h3">Pilih Jarak Lari</h1>
            <p class="mt-2 text-center text-body-sm text-muted">
                Tipe registrasi: <span class="font-semibold text-foreground">{{ $registrationType->label() }}</span>
                &middot;
                Tipe harga: <span class="font-semibold text-foreground">{{ $priceType->label() }}</span>
                &middot;
                <a href="{{ route('registration.price', $registrationType) }}" class="font-semibold text-primary hover:underline">Ganti</a>
            </p>

            <div class="mt-6 flex flex-col gap-4 md:mt-8 md:gap-5">
                @foreach ($categories as $category)
                    @php
                        // Only Early Bird has limited slots; Normal Price shows no slot status.
                        $slotsLeftForCategory = $slotsLeft[$category->value] ?? null;
                        $slotStatus = match (true) {
                            $slotsLeftForCategory === null => null,
                            $slotsLeftForCategory > 0 => 'Sisa '.NumberFormat::integer($slotsLeftForCategory).' slot',
                            default => 'Sold out',
                        };
                    @endphp
                    <x-choice-card
                        :href="route('registration.create', [$registrationType, $priceType->slug(), $category])"
                        :title="$category->distance()"
                        :meta="$priceType->label().' · '.Rupiah::format($category->feeFor($registrationType, $priceType))"
                        :status="$slotStatus"
                        :disabled="$slotsLeftForCategory === 0"
                    />
                @endforeach
            </div>

            <p class="mt-6 text-center text-body-sm text-muted">
                Bingung memilih?
                <a href="{{ route('race.category') }}" class="font-semibold text-primary hover:underline">Lihat detail tiap kategori</a>
            </p>
        </x-panel>
    </div>
</x-layouts.app>
