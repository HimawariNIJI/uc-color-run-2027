<x-layouts.app title="Pilih Jarak Lari" description="Pilih kategori jarak lari UC Color Run 2027: 3 KM, 5 KM, atau 10 KM.">
    <div class="page-container page-section">
        <x-panel>
            <x-registration-steps :current="3" />
            <h1 class="mt-6 text-center font-display text-h3 font-normal uppercase text-primary">Pilih Jarak Lari</h1>
            <p class="mt-2 text-center text-body-sm text-muted">
                Tipe registrasi: <span class="font-semibold text-foreground">{{ $registrationType->label() }}</span>
                &middot;
                Tipe harga: <span class="font-semibold text-foreground">{{ $priceType->label() }}</span>
                &middot;
                <a href="{{ route('registration.price', $registrationType) }}" class="font-semibold text-primary hover:underline">Ganti</a>
            </p>

            @unless ($packages->contains->isOpen())
                <x-alert tone="info" class="mt-6">Paket pada tipe harga ini belum dibuka untuk pendaftaran. Paket dan kuota di bawah sudah resmi.</x-alert>
            @endunless

            <div class="mt-6 flex flex-col gap-4 md:mt-8 md:gap-5">
                @foreach ($packages as $package)
                    <x-choice-card
                        :href="route('registration.create', [$registrationType, $priceType->slug(), $package->race_category])"
                        :title="$package->race_category->distance()"
                        :meta="$package->name().' '.$package->eligibility().' · '.$package->priceLabel()"
                        :status="$package->quotaLabel()"
                        :disabled="! $package->isOpen()"
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
