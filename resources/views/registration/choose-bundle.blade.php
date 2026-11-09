<x-layouts.app title="Pilih Paket Bundle" description="Paket bundle UC Color Run 2027: Family Bundle 3K dan Couple Bundle 5K.">
    <div class="page-container page-section">
        <x-panel>
            <x-registration-steps :current="2" />
            <h1 class="mt-6 text-center font-display text-h3 font-normal uppercase text-primary">Pilih Paket Bundle</h1>
            <p class="mt-2 text-center text-body-sm text-muted">
                Tipe registrasi: <span class="font-semibold text-foreground">{{ $registrationType->label() }}</span>
                &middot;
                <a href="{{ route('registration.type') }}" class="font-semibold text-primary hover:underline">Ganti</a>
            </p>

            @unless ($bundles->contains->isOpen())
                <x-alert tone="info" class="mt-6">Paket bundle belum dibuka untuk pendaftaran.</x-alert>
            @endunless

            <div class="mt-6 flex flex-col gap-4 md:mt-8 md:gap-5">
                @foreach ($bundles as $bundle)
                    <x-choice-card
                        :href="route('registration.bundle.create', $bundle->bundle_type)"
                        :title="$bundle->name()"
                        :description="$bundle->race_category->distance().' · '.$bundle->eligibility()"
                        icon="package"
                        :meta="$bundle->priceLabel().' per paket'"
                        :status="$bundle->quotaLabel()"
                        :disabled="! $bundle->isOpen()"
                    />
                @endforeach
            </div>

            <p class="mt-6 text-center text-body-sm text-muted">Satu kuota bundle berlaku untuk satu paket, berapa pun jumlah pesertanya di dalam paket.</p>
        </x-panel>
    </div>
</x-layouts.app>
